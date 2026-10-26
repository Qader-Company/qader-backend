<?php

namespace App\Modules\V1\TalentApplications\Application\Services;

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\Admins\Domain\Models\Admin;
use App\Modules\V1\TalentApplications\Domain\Models\TalentApplication;
use App\Modules\V1\TalentApplications\Domain\Models\TalentApplicationEvent;
use App\Modules\V1\TalentApplications\Domain\Models\TalentBlacklistEntry;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\TalentApplicationStatusEnum;
use App\Modules\V1\Users\Application\Services\UserAccessResolver;
use App\Modules\V1\Users\Domain\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class AdminTalentStateService
{
    /** @return array{talents: LengthAwarePaginator, summary: array<string, int>} */
    public function paginate(array $filters, User $user): array
    {
        $base = TalentApplication::query()->whereIn('status', [
            TalentApplicationStatusEnum::ACCEPTED->value,
            TalentApplicationStatusEnum::REJECTED->value,
        ]);
        $this->applySearch($base, $filters['search'] ?? '');

        $summary = [
            'accepted' => (clone $base)->where('status', 'accepted')->whereDoesntHave('blacklistEntry', fn (Builder $q) => $q->whereNull('removed_at'))->count(),
            'rejected_before_interview' => (clone $base)->where('status', 'rejected')->doesntHave('interviews')->whereDoesntHave('blacklistEntry', fn (Builder $q) => $q->whereNull('removed_at'))->count(),
            'rejected_after_interview' => (clone $base)->where('status', 'rejected')->has('interviews')->whereDoesntHave('blacklistEntry', fn (Builder $q) => $q->whereNull('removed_at'))->count(),
            'blacklisted' => (clone $base)->whereHas('blacklistEntry', fn (Builder $q) => $q->whereNull('removed_at'))->count(),
        ];

        $query = clone $base;
        $states = $filters['state'] ?? [];
        if ($states !== []) {
            $query->where(function (Builder $query) use ($states): void {
                if (in_array('blacklisted', $states, true)) {
                    $query->orWhereHas('blacklistEntry', fn (Builder $q) => $q->whereNull('removed_at'));
                }
                foreach (['accepted', 'rejected'] as $state) {
                    if (in_array($state, $states, true)) {
                        $query->orWhere(fn (Builder $q) => $q
                            ->where('status', $state)
                            ->whereDoesntHave('blacklistEntry', fn (Builder $b) => $b->whereNull('removed_at')));
                    }
                }
            });
        }
        if (($filters['rejection_stage'] ?? null) === 'before_interview') {
            $query->where('status', 'rejected')->doesntHave('interviews');
        } elseif (($filters['rejection_stage'] ?? null) === 'after_interview') {
            $query->where('status', 'rejected')->has('interviews');
        }

        $paginator = $query->with(['talent.user', 'blacklistEntry', 'interviews'])
            ->orderBy('review_decided_at', ($filters['sort'] ?? 'newest') === 'oldest' ? 'asc' : 'desc')
            ->paginate($filters['per_page'] ?? 20);
        $paginator->setCollection($paginator->getCollection()->map(function (TalentApplication $application) use ($user): TalentApplication {
            $application->setAttribute('available_actions', $this->availableActions($application, $user));

            return $application;
        }));

        return ['talents' => $paginator, 'summary' => $summary];
    }

    public function find(int $talentId, User $user): TalentApplication
    {
        $application = $this->applicationQuery($talentId)
            ->with(['talent.user', 'blacklistEntry', 'interviews.evaluation.evaluator.user', 'evaluation.tips', 'evaluation.currency.translations'])
            ->firstOrFail();
        $application->setAttribute('available_actions', $this->availableActions($application, $user));

        return $application;
    }

    public function addToBlacklist(int $talentId, string $reason, Admin $actor): TalentApplication
    {
        return DB::transaction(function () use ($talentId, $reason, $actor): TalentApplication {
            $application = $this->applicationQuery($talentId)->lockForUpdate()->firstOrFail();
            $this->ensureFinalState($application);
            $entry = TalentBlacklistEntry::query()->where('application_id', $application->id)->lockForUpdate()->first();
            if ($entry?->removed_at === null && $entry !== null) {
                throw new ConflictHttpException(__('talents.blacklist.already_blacklisted'));
            }
            TalentBlacklistEntry::query()->updateOrCreate(
                ['application_id' => $application->id],
                [
                    'talent_id' => $application->talent_id,
                    'blacklist_reason' => $reason,
                    'blocked_by_admin_id' => $actor->id,
                    'blocked_at' => now(),
                    'removed_at' => null,
                    'removed_by_admin_id' => null,
                ],
            );
            $this->event($application, $actor, 'talent_added_to_blacklist');

            return $this->reload($application, $actor->user);
        });
    }

    public function removeFromBlacklist(int $talentId, ?int $months, Admin $actor): TalentApplication
    {
        return DB::transaction(function () use ($talentId, $months, $actor): TalentApplication {
            $application = $this->applicationQuery($talentId)->lockForUpdate()->firstOrFail();
            $entry = TalentBlacklistEntry::query()
                ->where('application_id', $application->id)
                ->whereNull('removed_at')
                ->lockForUpdate()
                ->first();
            if ($entry === null) {
                return $this->reload($application, $actor->user);
            }
            if ($application->status === TalentApplicationStatusEnum::REJECTED && $months === null) {
                throw ValidationException::withMessages([
                    'reapply_after_months' => [__('talents.blacklist.reapply_period_required')],
                ]);
            }
            $removedAt = now();
            $entry->update(['removed_at' => $removedAt, 'removed_by_admin_id' => $actor->id]);
            if ($application->status === TalentApplicationStatusEnum::REJECTED) {
                $application->update(['reapply_at' => $removedAt->copy()->addMonthsNoOverflow($months)]);
            }
            $this->event($application, $actor, 'talent_removed_from_blacklist', ['reapply_after_months' => $months]);

            return $this->reload($application, $actor->user);
        });
    }

    public function editReapply(int $talentId, int $months, Admin $actor): TalentApplication
    {
        return DB::transaction(function () use ($talentId, $months, $actor): TalentApplication {
            $application = $this->applicationQuery($talentId)->lockForUpdate()->firstOrFail();
            if (
                $application->status !== TalentApplicationStatusEnum::REJECTED
                || $application->blacklistEntry()->whereNull('removed_at')->exists()
            ) {
                throw new ConflictHttpException(__('talents.blacklist.reapply_not_editable'));
            }
            $application->update([
                'reapply_at' => $application->review_decided_at->copy()->addMonthsNoOverflow($months),
            ]);
            $this->event($application, $actor, 'talent_reapply_updated', ['reapply_after_months' => $months]);

            return $this->reload($application, $actor->user);
        });
    }

    /** @return array<int, string> */
    public function availableActions(TalentApplication $application, User $user): array
    {
        $permissions = UserAccessResolver::resolve($user)['permissions'];
        $has = static fn (AdminPermissionEnum $permission): bool => in_array($permission->value, $permissions, true);
        $blacklisted = $application->blacklistEntry?->removed_at === null && $application->blacklistEntry !== null;
        $actions = [];
        if ($blacklisted && $has(AdminPermissionEnum::TALENTS_MANAGE_BLACKLIST)) {
            $actions[] = 'remove_from_blacklist';
        }
        if (! $blacklisted && $has(AdminPermissionEnum::TALENTS_MANAGE_BLACKLIST)) {
            $actions[] = 'add_to_blacklist';
        }
        if (! $blacklisted && $application->status === TalentApplicationStatusEnum::REJECTED && $has(AdminPermissionEnum::TALENTS_MANAGE_REAPPLY)) {
            $actions[] = 'edit_reapply';
        }

        return $actions;
    }

    private function applicationQuery(int $talentId): Builder
    {
        return TalentApplication::query()->where('talent_id', $talentId)->whereIn('status', ['accepted', 'rejected']);
    }

    private function applySearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }
        $like = '%'.$search.'%';
        $query->whereHas('talent.user', fn (Builder $q) => $q
            ->where('first_name', 'like', $like)
            ->orWhere('last_name', 'like', $like)
            ->orWhere('email', 'like', $like));
    }

    private function ensureFinalState(TalentApplication $application): void
    {
        if (! in_array($application->status, [TalentApplicationStatusEnum::ACCEPTED, TalentApplicationStatusEnum::REJECTED], true)) {
            throw new ConflictHttpException(__('talents.blacklist.final_state_required'));
        }
    }

    private function reload(TalentApplication $application, User $user): TalentApplication
    {
        return $this->find($application->talent_id, $user);
    }

    private function event(TalentApplication $application, Admin $actor, string $action, ?array $data = null): void
    {
        TalentApplicationEvent::query()->create([
            'application_id' => $application->id,
            'admin_id' => $actor->id,
            'action' => $action,
            'data' => $data,
            'created_at' => now(),
        ]);
    }
}
