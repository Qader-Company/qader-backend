<?php

namespace App\Modules\V1\HiringRequests\Application\Services;

use App\Modules\V1\Admins\Domain\Models\Admin;
use App\Modules\V1\HiringRequests\Domain\Models\HiringRequestItem;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringOfferStatusEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringRequestItemStatusEnum;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Workspaces\Domain\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class HiringItemControlWorkflow
{
    public const WORKSPACE_HOLD_SOURCE = 'workspace_deactivated';

    private const RELATIONS = ['hiringRequest.workspace', 'talent.user', 'jobTitle', 'latestOffer.currency.translations', 'offers.currency.translations', 'interview'];

    public function __construct(
        private readonly HiringRuntimeSettings $settings,
        private readonly HiringEventRecorder $events,
        private readonly HiringInterviewWorkflow $interviews,
    ) {}

    public function holdForClient(User $user, Workspace $workspace, int $itemId, ?string $reason): HiringRequestItem
    {
        return $this->hold($user, $itemId, $reason, fn (Builder $query) => $query->whereHas('hiringRequest', fn (Builder $request) => $request->where('workspace_id', $workspace->id)));
    }

    public function holdForAdmin(User $user, Admin $admin, int $itemId, ?string $reason): HiringRequestItem
    {
        return $this->hold($user, $itemId, $reason, fn (Builder $query) => $query->whereHas('hiringRequest', fn (Builder $request) => $request->where('assigned_admin_id', $admin->id)));
    }

    public function resumeForClient(User $user, Workspace $workspace, int $itemId): HiringRequestItem
    {
        return $this->resume($user, $itemId, fn (Builder $query) => $query->whereHas('hiringRequest', fn (Builder $request) => $request->where('workspace_id', $workspace->id)));
    }

    public function resumeForAdmin(User $user, Admin $admin, int $itemId): HiringRequestItem
    {
        return $this->resume($user, $itemId, fn (Builder $query) => $query->whereHas('hiringRequest', fn (Builder $request) => $request->where('assigned_admin_id', $admin->id)));
    }

    /**
     * Workspace deactivated (or deleted): pause every running item. Interviews are cancelled and
     * deadlines are cleared by the regular hold logic; the hold is tagged so activation can resume it.
     */
    public function holdForWorkspaceDeactivation(User $actor, Workspace $workspace): int
    {
        $scope = fn (Builder $query) => $query->whereHas('hiringRequest', fn (Builder $request) => $request->where('workspace_id', $workspace->id));
        $itemIds = HiringRequestItem::query()
            ->tap($scope)
            ->whereIn('status', [
                HiringRequestItemStatusEnum::OFFER->value,
                HiringRequestItemStatusEnum::INTERVIEW->value,
                HiringRequestItemStatusEnum::ACCEPTED->value,
            ])
            ->orderBy('id')
            ->pluck('id');

        foreach ($itemIds as $itemId) {
            $this->hold($actor, $itemId, __('hiring.held_by_workspace_deactivation'), $scope, self::WORKSPACE_HOLD_SOURCE, true);
        }

        return $itemIds->count();
    }

    /** Workspace reactivated: resume only the items that its deactivation put on hold, with fresh deadlines. */
    public function resumeAfterWorkspaceActivation(User $actor, Workspace $workspace): int
    {
        $scope = fn (Builder $query) => $query->whereHas('hiringRequest', fn (Builder $request) => $request->where('workspace_id', $workspace->id));
        $resumed = 0;

        $items = HiringRequestItem::query()
            ->tap($scope)
            ->where('status', HiringRequestItemStatusEnum::ON_HOLD->value)
            ->orderBy('id')
            ->get();

        foreach ($items as $item) {
            $hold = $item->events()->where('action', 'item_held')->first();
            if (data_get($hold?->data, 'source') !== self::WORKSPACE_HOLD_SOURCE) {
                continue;
            }
            $this->resume($actor, $item->id, $scope, true);
            $resumed++;
        }

        return $resumed;
    }

    public function cancelForClient(User $user, Workspace $workspace, int $itemId, ?string $reason): HiringRequestItem
    {
        return $this->cancel($user, $itemId, $reason, fn (Builder $query) => $query->whereHas('hiringRequest', fn (Builder $request) => $request->where('workspace_id', $workspace->id)));
    }

    public function cancelForAdmin(User $user, Admin $admin, int $itemId, ?string $reason): HiringRequestItem
    {
        return $this->cancel($user, $itemId, $reason, fn (Builder $query) => $query->whereHas('hiringRequest', fn (Builder $request) => $request->where('assigned_admin_id', $admin->id)));
    }

    private function hold(User $actor, int $itemId, ?string $reason, callable $scope, ?string $source = null, bool $system = false): HiringRequestItem
    {
        return DB::transaction(function () use ($actor, $itemId, $reason, $scope, $source, $system): HiringRequestItem {
            $item = $this->locked($itemId, $scope);
            if (! $system) {
                $this->ensureWorkspaceActive($item);
            }
            if (! $this->isOperational($item->status) || $item->status === HiringRequestItemStatusEnum::ON_HOLD) {
                throw new ConflictHttpException(__('hiring.hold_not_allowed'));
            }
            $previousStatus = $item->status;
            $previousOfferDueAt = $item->latestOffer?->response_due_at;
            $previousDecisionDueAt = $item->decision_due_at;
            $this->interviews->cancelForItem($item, $actor, 'interview_cancelled_by_hold');
            if ($item->latestOffer?->status === HiringOfferStatusEnum::PENDING) {
                $item->latestOffer->update(['response_due_at' => null]);
            }
            $item->update(['status' => HiringRequestItemStatusEnum::ON_HOLD, 'hold_reason' => $reason, 'decision_due_at' => null]);
            $this->events->record($item, $actor, 'item_held', [
                'previous_status' => $previousStatus->value,
                'previous_offer_due_at' => $previousOfferDueAt,
                'previous_decision_due_at' => $previousDecisionDueAt,
                'reason' => $reason,
                ...($source === null ? [] : ['source' => $source]),
            ]);

            return $this->load($item);
        });
    }

    private function resume(User $actor, int $itemId, callable $scope, bool $system = false): HiringRequestItem
    {
        return DB::transaction(function () use ($actor, $itemId, $scope, $system): HiringRequestItem {
            $item = $this->locked($itemId, $scope);
            if (! $system) {
                $this->ensureWorkspaceActive($item);
            }
            if ($item->status !== HiringRequestItemStatusEnum::ON_HOLD) {
                throw new ConflictHttpException(__('hiring.resume_not_allowed'));
            }
            $hold = $item->events()->where('action', 'item_held')->lockForUpdate()->first();
            $previous = HiringRequestItemStatusEnum::tryFrom((string) data_get($hold?->data, 'previous_status'));
            $next = $previous ?? HiringRequestItemStatusEnum::OFFER;
            $updates = ['status' => $next, 'hold_reason' => null];
            if ($next === HiringRequestItemStatusEnum::INTERVIEW && data_get($hold?->data, 'previous_decision_due_at') !== null) {
                $updates['decision_due_at'] = now()->addHours($this->settings->decisionResponseHours());
            }
            if ($next === HiringRequestItemStatusEnum::OFFER && $item->latestOffer?->status === HiringOfferStatusEnum::PENDING) {
                $item->latestOffer->update(['response_due_at' => now()->addHours($this->settings->offerResponseHours())]);
            }
            $item->update($updates);
            $this->events->record($item, $actor, 'item_resumed', ['resumed_to' => $next->value]);

            return $this->load($item);
        });
    }

    private function cancel(User $actor, int $itemId, ?string $reason, callable $scope): HiringRequestItem
    {
        return DB::transaction(function () use ($actor, $itemId, $reason, $scope): HiringRequestItem {
            $item = $this->locked($itemId, $scope);
            $this->ensureWorkspaceActive($item);
            if (! $this->isOperational($item->status) && $item->status !== HiringRequestItemStatusEnum::ON_HOLD) {
                throw new ConflictHttpException(__('hiring.cancel_not_allowed'));
            }
            $previous = $item->status;
            $this->interviews->cancelForItem($item, $actor, 'interview_cancelled_with_item');
            $item->update(['status' => HiringRequestItemStatusEnum::CANCELLED, 'cancellation_reason' => $reason, 'decision_due_at' => null]);
            $this->events->record($item, $actor, 'item_cancelled', ['previous_status' => $previous->value, 'reason' => $reason]);

            return $this->load($item);
        });
    }

    private function locked(int $itemId, callable $scope): HiringRequestItem
    {
        $query = HiringRequestItem::query();
        $scope($query);

        return $query->with(['latestOffer', 'interview'])->lockForUpdate()->findOrFail($itemId);
    }

    private function ensureWorkspaceActive(HiringRequestItem $item): void
    {
        $workspace = $item->loadMissing('hiringRequest.workspace')->hiringRequest?->workspace;
        if ($workspace !== null && ($workspace->trashed() || ! $workspace->isActive())) {
            throw new ConflictHttpException(__('workspaces.inactive'));
        }
    }

    private function isOperational(HiringRequestItemStatusEnum $status): bool
    {
        return ! in_array($status, [
            HiringRequestItemStatusEnum::PENDING,
            HiringRequestItemStatusEnum::REJECTED, HiringRequestItemStatusEnum::CANCELLED,
            HiringRequestItemStatusEnum::COMPLETED,
        ], true);
    }

    private function load(HiringRequestItem $item): HiringRequestItem
    {
        return $item->refresh()->load(self::RELATIONS);
    }
}
