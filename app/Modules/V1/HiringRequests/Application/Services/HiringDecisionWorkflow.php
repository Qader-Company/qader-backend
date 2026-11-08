<?php

namespace App\Modules\V1\HiringRequests\Application\Services;

use App\Modules\V1\Admins\Domain\Models\Admin;
use App\Modules\V1\HiringRequests\Domain\Models\HiringRequestItem;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringDecisionEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringRequestItemStatusEnum;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Workspaces\Domain\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class HiringDecisionWorkflow
{
    private const RELATIONS = [
        'hiringRequest.workspace', 'talent.user', 'talent.jobTitle', 'jobTitle',
        'latestOffer.currency.translations', 'offers.currency.translations', 'interview',
    ];

    public function __construct(private readonly HiringEventRecorder $events) {}

    public function submitAsTalent(User $user, int $itemId, HiringDecisionEnum $decision, ?string $reason): HiringRequestItem
    {
        $talent = $user->talent()->firstOrFail();

        return $this->submit($user, $itemId, 'talent', $decision, $reason, false,
            fn (Builder $query) => $query->where('talent_id', $talent->id));
    }

    public function submitAsClient(User $user, Workspace $workspace, int $itemId, HiringDecisionEnum $decision, ?string $reason): HiringRequestItem
    {
        return $this->submit($user, $itemId, 'client', $decision, $reason, false,
            fn (Builder $query) => $query->whereHas('hiringRequest', fn (Builder $request) => $request->where('workspace_id', $workspace->id)));
    }

    public function submitOnBehalf(User $user, Admin $admin, int $itemId, string $party, HiringDecisionEnum $decision, ?string $reason): HiringRequestItem
    {
        return $this->submit($user, $itemId, $party, $decision, $reason, true,
            fn (Builder $query) => $query->whereHas('hiringRequest', fn (Builder $request) => $request->where('assigned_admin_id', $admin->id)));
    }

    public function withdrawAsTalent(User $user, int $itemId): HiringRequestItem
    {
        $talent = $user->talent()->firstOrFail();

        return $this->withdraw($user, $itemId, 'talent',
            fn (Builder $query) => $query->where('talent_id', $talent->id));
    }

    public function withdrawAsClient(User $user, Workspace $workspace, int $itemId): HiringRequestItem
    {
        return $this->withdraw($user, $itemId, 'client',
            fn (Builder $query) => $query->whereHas('hiringRequest', fn (Builder $request) => $request->where('workspace_id', $workspace->id)));
    }

    public function withdrawOnBehalf(User $user, Admin $admin, int $itemId, string $party): HiringRequestItem
    {
        return $this->withdraw($user, $itemId, $party,
            fn (Builder $query) => $query->whereHas('hiringRequest', fn (Builder $request) => $request->where('assigned_admin_id', $admin->id)));
    }

    private function submit(User $actor, int $itemId, string $party, HiringDecisionEnum $decision, ?string $reason, bool $adminOverride, callable $scope): HiringRequestItem
    {
        return DB::transaction(function () use ($actor, $itemId, $party, $decision, $reason, $adminOverride, $scope): HiringRequestItem {
            $query = HiringRequestItem::query();
            $scope($query);
            $item = $query->lockForUpdate()->findOrFail($itemId);
            if ($item->status !== HiringRequestItemStatusEnum::INTERVIEW || $item->decision_due_at === null || $item->{$party.'_decision'} !== null) {
                throw new ConflictHttpException(__('hiring.decision_not_allowed'));
            }
            if (! $adminOverride && ($item->decision_due_at === null || now()->gt($item->decision_due_at))) {
                throw new ConflictHttpException(__('hiring.decision_deadline_expired'));
            }
            $item->update([
                $party.'_decision' => $decision,
                $party.'_decision_reason' => $reason,
                $party.'_decided_at' => now(),
            ]);
            $item->refresh();
            $nextStatus = $item->client_decision === HiringDecisionEnum::REJECT || $item->talent_decision === HiringDecisionEnum::REJECT
                ? HiringRequestItemStatusEnum::REJECTED
                : ($item->client_decision === HiringDecisionEnum::PROCEED && $item->talent_decision === HiringDecisionEnum::PROCEED
                    ? HiringRequestItemStatusEnum::ACCEPTED
                    : HiringRequestItemStatusEnum::INTERVIEW);
            $item->update(['status' => $nextStatus]);
            $this->events->record($item, $actor, $adminOverride ? 'decision_recorded_on_behalf' : 'decision_recorded', [
                'party' => $party, 'decision' => $decision->value, 'reason' => $reason,
            ]);

            return $item->refresh()->load(self::RELATIONS);
        });
    }

    private function withdraw(User $actor, int $itemId, string $party, callable $scope): HiringRequestItem
    {
        return DB::transaction(function () use ($actor, $itemId, $party, $scope): HiringRequestItem {
            $query = HiringRequestItem::query();
            $scope($query);
            $item = $query->lockForUpdate()->findOrFail($itemId);
            if (! in_array($item->status, [HiringRequestItemStatusEnum::INTERVIEW, HiringRequestItemStatusEnum::REJECTED, HiringRequestItemStatusEnum::ACCEPTED], true)
                || $item->{$party.'_decision'} === null) {
                throw new ConflictHttpException(__('hiring.decision_withdrawal_not_allowed'));
            }
            $previous = $item->{$party.'_decision'};
            $item->update([
                $party.'_decision' => null,
                $party.'_decision_reason' => null,
                $party.'_decided_at' => null,
                'status' => HiringRequestItemStatusEnum::INTERVIEW,
            ]);
            $this->events->record($item, $actor, 'decision_withdrawn', [
                'party' => $party, 'previous_decision' => $previous->value,
            ]);

            return $item->refresh()->load(self::RELATIONS);
        });
    }
}
