<?php

namespace App\Modules\V1\HiringRequests\Application\Services;

use App\Modules\V1\Admins\Domain\Models\Admin;
use App\Modules\V1\HiringRequests\Domain\Models\HiringOffer;
use App\Modules\V1\HiringRequests\Domain\Models\HiringRequestItem;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringOfferPartyEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringOfferStatusEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringRequestItemStatusEnum;
use App\Modules\V1\Talents\Domain\Models\Talent;
use App\Modules\V1\Users\Domain\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class HiringOfferWorkflow
{
    private const ITEM_RELATIONS = [
        'hiringRequest.workspace.media', 'talent.user', 'talent.jobTitle', 'jobTitle',
        'latestOffer.currency.translations', 'offers.currency.translations', 'interview',
    ];

    public function __construct(
        private readonly HiringRuntimeSettings $settings,
        private readonly HiringEventRecorder $events,
        private readonly HiringOfferExpirationService $expiration,
    ) {}

    public function paginateForTalent(User $user, array $filters): LengthAwarePaginator
    {
        $talent = $this->talent($user);
        $this->expiration->synchronizeDue();

        return HiringRequestItem::query()
            ->where('talent_id', $talent->id)
            ->whereHas('offers', fn (Builder $query) => $query->whereNotNull('sent_at'))
            ->when($filters['status'] ?? null, fn (Builder $query, array $statuses) => $query->whereIn('status', $statuses))
            ->when($filters['offer_status'] ?? null, fn (Builder $query, array $statuses) => $query->whereHas('latestOffer', fn (Builder $offer) => $offer->whereIn('status', $statuses)))
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(function (Builder $query) use ($like): void {
                    $query->where('reference', 'like', $like)
                        ->orWhereHas('jobTitle', fn (Builder $jobTitle) => $jobTitle->where('name', 'like', $like))
                        ->orWhereHas('hiringRequest.workspace', fn (Builder $workspace) => $workspace->where('name', 'like', $like));
                });
            })
            ->with(self::ITEM_RELATIONS)
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function showForTalent(User $user, int $itemId): HiringRequestItem
    {
        $talent = $this->talent($user);
        $item = HiringRequestItem::query()->where('talent_id', $talent->id)
            ->with(self::ITEM_RELATIONS)->findOrFail($itemId);
        if ($item->latestOffer?->id) {
            $this->expiration->expire($item->latestOffer->id);
        }

        return $item->refresh()->load(self::ITEM_RELATIONS);
    }

    public function talentAccept(User $user, int $itemId): HiringRequestItem
    {
        return $this->talentDecision($user, $itemId, HiringOfferStatusEnum::ACCEPTED);
    }

    public function talentReject(User $user, int $itemId): HiringRequestItem
    {
        return $this->talentDecision($user, $itemId, HiringOfferStatusEnum::REJECTED);
    }

    public function talentCounter(User $user, int $itemId, array $terms): HiringRequestItem
    {
        $talent = $this->talent($user);

        return DB::transaction(function () use ($user, $talent, $itemId, $terms): HiringRequestItem {
            $item = $this->lockTalentItem($talent, $itemId);
            $offer = $this->lockLatestOffer($item);
            $this->ensureTalentCanRespond($item, $offer);
            $offer->update(['status' => HiringOfferStatusEnum::COUNTERED]);
            $counter = $item->offers()->create([
                ...Arr::only($terms, [
                    'salary_amount', 'currency_id', 'work_type', 'work_mode',
                    'work_start_day', 'work_end_day', 'hybrid_work_days',
                    'work_hours', 'notice_period_days', 'work_location',
                ]),
                'notice_period_days' => $terms['notice_period_days'] ?? $talent->notice_period_days,
                'work_location' => $terms['work_location'] ?? $this->workspaceLocation($item),
                'proposed_by' => HiringOfferPartyEnum::TALENT,
                'status' => HiringOfferStatusEnum::PENDING,
                'sent_at' => now(),
                'response_due_at' => null,
            ]);
            $item->update(['status' => HiringRequestItemStatusEnum::OFFER]);
            $this->events->record($item, $user, 'offer_countered', [
                'previous_offer_id' => $offer->id,
                'offer_id' => $counter->id,
                'proposed_by' => HiringOfferPartyEnum::TALENT->value,
            ]);

            return $item->refresh()->load(self::ITEM_RELATIONS);
        });
    }

    public function sendClientOffer(User $user, Admin $admin, int $itemId, array $terms): HiringRequestItem
    {
        return DB::transaction(function () use ($user, $admin, $itemId, $terms): HiringRequestItem {
            $item = $this->lockAdminItem($admin, $itemId);
            $latest = $this->lockLatestOffer($item);
            $workspace = $item->hiringRequest?->workspace;
            if ($workspace !== null && ($workspace->trashed() || ! $workspace->isActive())) {
                throw new ConflictHttpException(__('workspaces.inactive'));
            }
            // A revised offer is only part of the offer-stage negotiation; it must not revive closed or paused items.
            if ($item->status !== HiringRequestItemStatusEnum::OFFER) {
                throw new ConflictHttpException(__('hiring.offer_not_actionable'));
            }
            if ($latest->status === HiringOfferStatusEnum::PENDING) {
                $latest->update(['status' => HiringOfferStatusEnum::COUNTERED]);
            }
            $sentAt = now();
            $offer = $item->offers()->create([
                ...Arr::only($terms, [
                    'salary_amount', 'currency_id', 'work_type', 'work_mode',
                    'work_start_day', 'work_end_day', 'hybrid_work_days',
                    'work_hours', 'notice_period_days', 'work_location',
                ]),
                'notice_period_days' => $terms['notice_period_days'] ?? $item->talent->notice_period_days,
                'work_location' => $terms['work_location'] ?? $this->workspaceLocation($item),
                'proposed_by' => HiringOfferPartyEnum::CLIENT,
                'status' => HiringOfferStatusEnum::PENDING,
                'sent_at' => $sentAt,
                'response_due_at' => $sentAt->copy()->addHours($this->settings->offerResponseHours()),
            ]);
            $item->update(['status' => HiringRequestItemStatusEnum::OFFER]);
            $this->events->record($item, $user, 'offer_sent', [
                'offer_id' => $offer->id,
                'proposed_by' => HiringOfferPartyEnum::CLIENT->value,
                'response_due_at' => $offer->response_due_at,
            ]);

            return $item->refresh()->load(self::ITEM_RELATIONS);
        });
    }

    public function respondOnBehalf(
        User $user,
        Admin $admin,
        int $itemId,
        HiringOfferStatusEnum $decision,
        ?string $reason,
    ): HiringRequestItem {
        return DB::transaction(function () use ($user, $admin, $itemId, $decision, $reason): HiringRequestItem {
            $item = $this->lockAdminItem($admin, $itemId);
            $offer = $this->lockLatestOffer($item);
            $workspace = $item->hiringRequest?->workspace;
            if ($workspace !== null && ($workspace->trashed() || ! $workspace->isActive())) {
                throw new ConflictHttpException(__('workspaces.inactive'));
            }
            // Only an item still in the offer stage can take an offer answer; cancelled / on-hold / decided items cannot.
            if (
                $item->status !== HiringRequestItemStatusEnum::OFFER
                || ! in_array($offer->status, [HiringOfferStatusEnum::PENDING, HiringOfferStatusEnum::EXPIRED], true)
            ) {
                throw new ConflictHttpException(__('hiring.offer_not_actionable'));
            }
            $wasExpired = $offer->status === HiringOfferStatusEnum::EXPIRED;
            $offer->update(['status' => $decision]);
            $this->applyOfferDecision($item, $decision, $reason);
            $this->events->record($item, $user, 'offer_responded_on_behalf', [
                'offer_id' => $offer->id,
                'decision' => $decision->value,
                'reason' => $reason,
                'after_expiry' => $wasExpired,
            ]);

            return $item->refresh()->load(self::ITEM_RELATIONS);
        });
    }

    private function talentDecision(User $user, int $itemId, HiringOfferStatusEnum $decision): HiringRequestItem
    {
        $talent = $this->talent($user);

        return DB::transaction(function () use ($user, $talent, $itemId, $decision): HiringRequestItem {
            $item = $this->lockTalentItem($talent, $itemId);
            $offer = $this->lockLatestOffer($item);
            $this->ensureTalentCanRespond($item, $offer);
            $offer->update(['status' => $decision]);
            $this->applyOfferDecision($item, $decision, null);
            $this->events->record($item, $user, $decision === HiringOfferStatusEnum::ACCEPTED ? 'offer_accepted' : 'offer_rejected', [
                'offer_id' => $offer->id,
            ]);

            return $item->refresh()->load(self::ITEM_RELATIONS);
        });
    }

    private function applyOfferDecision(HiringRequestItem $item, HiringOfferStatusEnum $decision, ?string $reason): void
    {
        if ($decision === HiringOfferStatusEnum::REJECTED) {
            $item->update(['status' => HiringRequestItemStatusEnum::REJECTED, 'rejection_reason' => $reason]);

            return;
        }
        $item->update(['status' => $item->interview_required
            ? HiringRequestItemStatusEnum::INTERVIEW
            : HiringRequestItemStatusEnum::ACCEPTED]);
    }

    private function ensureTalentCanRespond(HiringRequestItem $item, HiringOffer $offer): void
    {
        $this->expiration->expire($offer->id);
        $offer->refresh();
        if (
            $offer->status !== HiringOfferStatusEnum::PENDING
            || $offer->sent_at === null
            || $offer->proposed_by !== HiringOfferPartyEnum::CLIENT
            || $item->status !== HiringRequestItemStatusEnum::OFFER
        ) {
            throw new ConflictHttpException(__('hiring.offer_not_actionable'));
        }
    }

    private function lockTalentItem(Talent $talent, int $itemId): HiringRequestItem
    {
        return HiringRequestItem::query()->where('talent_id', $talent->id)
            ->with(['hiringRequest.workspace.city.translations', 'hiringRequest.workspace.country.translations', 'talent'])
            ->lockForUpdate()->findOrFail($itemId);
    }

    private function lockAdminItem(Admin $admin, int $itemId): HiringRequestItem
    {
        return HiringRequestItem::query()
            ->whereHas('hiringRequest', fn (Builder $query) => $query->where('assigned_admin_id', $admin->id))
            ->with(['hiringRequest.workspace.city.translations', 'hiringRequest.workspace.country.translations', 'talent'])
            ->lockForUpdate()->findOrFail($itemId);
    }

    private function lockLatestOffer(HiringRequestItem $item): HiringOffer
    {
        return HiringOffer::query()->where('request_item_id', $item->id)->latest('id')->lockForUpdate()->firstOrFail();
    }

    private function talent(User $user): Talent
    {
        return $user->talent()->firstOrFail();
    }

    private function workspaceLocation(HiringRequestItem $item): ?string
    {
        $workspace = $item->hiringRequest->workspace;

        return collect([$workspace->city?->name, $workspace->country?->name])->filter()->implode(', ') ?: null;
    }
}
