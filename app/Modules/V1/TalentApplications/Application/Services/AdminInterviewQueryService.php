<?php

namespace App\Modules\V1\TalentApplications\Application\Services;

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\Admins\Domain\Models\Admin;
use App\Modules\V1\TalentApplications\Domain\Models\Interview;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\InterviewStatusEnum;
use App\Modules\V1\Users\Application\Services\UserAccessResolver;
use App\Modules\V1\Users\Domain\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AdminInterviewQueryService
{
    public function __construct(private readonly InterviewStateSynchronizer $synchronizer) {}

    public function paginate(array $filters, Admin $actor, User $user): LengthAwarePaginator
    {
        $query = Interview::query()->with([
            'application.talent.user', 'admins.user', 'slots', 'evaluation.evaluator.user',
        ]);

        if (($filters['scope'] ?? 'all') === 'my') {
            $query->whereHas('application', fn (Builder $query) => $query->where('assigned_admin_id', $actor->id));
        }
        if (isset($filters['interviewer_admin_id'])) {
            $query->whereHas('admins', fn (Builder $query) => $query->whereKey($filters['interviewer_admin_id']));
        }
        if (($filters['status'] ?? []) !== []) {
            $query->whereIn('status', $filters['status']);
        }
        if (($filters['type'] ?? []) !== []) {
            $query->whereIn('type', $filters['type']);
        }
        if (isset($filters['scheduled_from'])) {
            $query->whereHas('slots', fn (Builder $query) => $query->whereDate('starts_at', '>=', $filters['scheduled_from']));
        }
        if (isset($filters['scheduled_to'])) {
            $query->whereHas('slots', fn (Builder $query) => $query->whereDate('starts_at', '<=', $filters['scheduled_to']));
        }
        if (($filters['search'] ?? '') !== '') {
            $search = '%'.$filters['search'].'%';
            $query->whereHas('application', fn (Builder $query) => $query->where(
                fn (Builder $query) => $query
                    ->where('submission_snapshot->user->first_name', 'like', $search)
                    ->orWhere('submission_snapshot->user->last_name', 'like', $search)
                    ->orWhere('submission_snapshot->user->email', 'like', $search)
            ));
        }

        $paginator = $query
            ->orderBy('created_at', ($filters['sort'] ?? 'newest') === 'oldest' ? 'asc' : 'desc')
            ->paginate($filters['per_page'] ?? 20);

        $paginator->setCollection($paginator->getCollection()->map(function (Interview $interview) use ($actor, $user): Interview {
            $model = $this->synchronizer->synchronize($interview);
            $model->setAttribute('available_actions', $this->availableActions($model, $user, $actor));

            return $model;
        }));

        return $paginator;
    }

    public function findForDetails(int $interviewId, User $user, Admin $actor): Interview
    {
        $interview = Interview::query()->with([
            'application.talent.user', 'admins.user', 'slots', 'evaluation.evaluator.user',
            'application.interviews.admins.user', 'application.interviews.slots',
            'application.interviews.evaluation.evaluator.user',
        ])->findOrFail($interviewId);
        $interview = $this->synchronizer->synchronize($interview);
        $interview->load([
            'application.talent.user',
            'application.interviews.admins.user',
            'application.interviews.slots',
            'application.interviews.evaluation.evaluator.user',
        ]);
        $interview->setAttribute('available_actions', $this->availableActions($interview, $user, $actor));

        return $interview;
    }

    /** @return array<int, string> */
    public function availableActions(Interview $interview, User $user, Admin $actor): array
    {
        if ((string) $interview->application->assigned_admin_id !== (string) $actor->getKey()) {
            return [];
        }

        $permissions = UserAccessResolver::resolve($user)['permissions'];
        $has = static fn (AdminPermissionEnum $permission): bool => in_array($permission->value, $permissions, true);
        $actions = [];
        $slot = $interview->selectedSlot ?? $interview->slots->first(fn ($slot) => $slot->status->value === 'selected');
        $now = now();

        if (
            $interview->status === InterviewStatusEnum::CONFIRMED
            && $slot !== null
            && $now->betweenIncluded($slot->starts_at->copy()->subMinutes(15), $slot->ends_at)
            && $has(AdminPermissionEnum::INTERVIEWS_MANAGE)
        ) {
            $actions[] = 'start';
        }
        if ($interview->status === InterviewStatusEnum::IN_PROGRESS && $has(AdminPermissionEnum::INTERVIEWS_MANAGE)) {
            $actions[] = 'complete';
        }
        if ($interview->status === InterviewStatusEnum::COMPLETED && $has(AdminPermissionEnum::INTERVIEWS_EVALUATE)) {
            $actions[] = $interview->evaluation === null ? 'evaluate' : 'update_evaluation';
        }
        if (
            $interview->status === InterviewStatusEnum::COMPLETED
            && $interview->evaluation !== null
            && $has(AdminPermissionEnum::INTERVIEWS_DECIDE)
        ) {
            array_push($actions, 'accept', 'reject', 'schedule_another_interview');
        }

        return $actions;
    }
}
