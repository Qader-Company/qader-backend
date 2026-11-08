<?php

namespace App\Modules\V1\TalentApplications\Application\Services;

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\Admins\Domain\Models\Admin;
use App\Modules\V1\TalentApplications\Domain\Models\TalentApplication;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\TalentApplicationStatusEnum;
use App\Modules\V1\Users\Application\Services\UserAccessResolver;
use App\Modules\V1\Users\Domain\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TalentApplicationQueryService
{
    /** @return array{applications: LengthAwarePaginator, summary: array<string, int>} */
    public function paginate(array $filters, Admin $actor): array
    {
        $filtered = $this->filteredQuery($filters, $actor);
        $summaryQuery = clone $filtered;

        $summary = [
            'all' => (clone $summaryQuery)->count(),
            'pending' => (clone $summaryQuery)->whereIn('status', [
                TalentApplicationStatusEnum::SUBMITTED->value,
                TalentApplicationStatusEnum::UNDER_REVIEW->value,
                TalentApplicationStatusEnum::INTERVIEW_REQUIRED->value,
            ])->count(),
            'accepted' => (clone $summaryQuery)->where('status', TalentApplicationStatusEnum::ACCEPTED->value)->count(),
            'rejected' => (clone $summaryQuery)->where('status', TalentApplicationStatusEnum::REJECTED->value)->count(),
        ];

        if (($filters['status'] ?? []) !== []) {
            $filtered->whereIn('status', $filters['status']);
        }

        $sortDirection = ($filters['sort'] ?? 'newest') === 'oldest' ? 'asc' : 'desc';
        $applications = $filtered
            ->with(['assignedAdmin.user'])
            ->orderBy('submitted_at', $sortDirection)
            ->paginate($filters['per_page'] ?? 20);

        return compact('applications', 'summary');
    }

    public function findForDetails(int $applicationId): TalentApplication
    {
        return TalentApplication::query()
            ->with([
                'media',
                'assignedAdmin.user',
                'evaluation.tips',
                'evaluation.currency.translations',
                'evaluation.evaluator.user',
                'interview.admins.user',
                'interview.slots',
                'blacklistEntry',
            ])
            ->findOrFail($applicationId);
    }

    public function reviewers(): Collection
    {
        return Admin::query()
            ->with('user')
            ->whereHas('user', fn (Builder $query) => $query->where('is_active', true))
            ->get()
            ->filter(fn (Admin $admin): bool => UserAccessResolver::hasAnyPermission(
                $admin->user,
                [AdminPermissionEnum::TALENT_APPLICATIONS_REVIEW->value],
            ))
            ->values();
    }

    public function reviewerHistory(int $applicationId): Collection
    {
        $application = TalentApplication::query()->findOrFail($applicationId);

        return $application->reviewerAssignments()
            ->with(['reviewer.user', 'assignedBy.user'])
            ->orderByDesc('assigned_at')
            ->get();
    }

    public function activity(int $applicationId): Collection
    {
        $application = TalentApplication::query()->findOrFail($applicationId);

        return $application->events()
            ->with('admin.user')
            ->orderByDesc('created_at')
            ->get();
    }

    /** @return array<int, string> */
    public function availableActions(TalentApplication $application, User $user, Admin $actor): array
    {
        $permissions = UserAccessResolver::resolve($user)['permissions'];
        $has = static fn (AdminPermissionEnum $permission): bool => in_array($permission->value, $permissions, true);
        $status = $application->status instanceof TalentApplicationStatusEnum
            ? $application->status
            : TalentApplicationStatusEnum::from($application->status);
        $assignedToActor = $application->assigned_admin_id !== null
            && (string) $application->assigned_admin_id === (string) $actor->getKey();
        $actions = [];

        if (in_array($status, [TalentApplicationStatusEnum::SUBMITTED, TalentApplicationStatusEnum::UNDER_REVIEW], true)) {
            if ($application->assigned_admin_id === null && $has(AdminPermissionEnum::TALENT_APPLICATIONS_ASSIGN_REVIEWER)) {
                $actions[] = 'assign';
            }

            if ($application->assigned_admin_id !== null && ! $assignedToActor && $has(AdminPermissionEnum::TALENT_APPLICATIONS_ASSIGN_REVIEWER)) {
                $actions[] = 'reassign';
            }

            if ($has(AdminPermissionEnum::TALENT_APPLICATIONS_REVIEW) && ($application->assigned_admin_id === null || $assignedToActor)) {
                $actions[] = $application->evaluation === null ? 'evaluate' : 'update_evaluation';
            }
        }

        if ($status === TalentApplicationStatusEnum::UNDER_REVIEW && $assignedToActor && $application->evaluation !== null && $has(AdminPermissionEnum::TALENT_APPLICATIONS_DECIDE)) {
            $actions[] = 'reject';
            $actions[] = 'schedule_interview';
        }

        return $actions;
    }

    private function filteredQuery(array $filters, Admin $actor): Builder
    {
        $query = TalentApplication::query();

        if (($filters['scope'] ?? 'all') === 'my') {
            $query->where('assigned_admin_id', $actor->id);
        }

        if (isset($filters['reviewer_admin_id'])) {
            $query->where('assigned_admin_id', $filters['reviewer_admin_id']);
        }

        if (isset($filters['submitted_from'])) {
            $query->whereDate('submitted_at', '>=', $filters['submitted_from']);
        }

        if (isset($filters['submitted_to'])) {
            $query->whereDate('submitted_at', '<=', $filters['submitted_to']);
        }

        if (($filters['search'] ?? '') !== '') {
            $search = '%'.$filters['search'].'%';
            $query->where(function (Builder $query) use ($search): void {
                $query
                    ->where('submission_snapshot->user->first_name', 'like', $search)
                    ->orWhere('submission_snapshot->user->last_name', 'like', $search)
                    ->orWhere('submission_snapshot->user->email', 'like', $search);
            });
        }

        return $query;
    }
}
