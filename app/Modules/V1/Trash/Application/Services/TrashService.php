<?php

namespace App\Modules\V1\Trash\Application\Services;

use App\Modules\V1\AccessControl\Domain\Models\Role;
use App\Modules\V1\Trash\Domain\Models\TrashEvent;
use App\Modules\V1\Users\Domain\Models\User;
use App\Settings\TrashSettings;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class TrashService
{
    public function __construct(
        private readonly TrashRegistry $registry,
        private readonly TrashRelationshipGuard $guard,
        private readonly TrashSettings $settings,
    ) {}

    public function paginate(string $key, array $filters = [], ?callable $scope = null, ?array $availableActions = null): array
    {
        $query = $this->registry->query($key);
        $scope?->__invoke($query);
        $this->applyFilters($key, $query, $filters);
        $paginator = $query->latest('deleted_at')->latest('id')->paginate((int) ($filters['per_page'] ?? 20));

        return [
            'items' => $paginator->getCollection()->map(fn (Model $model) => $this->serialize($key, $model, $availableActions))->values(),
            'meta' => $this->pagination($paginator),
        ];
    }

    public function softDelete(string $key, int $id, ?User $actor = null): void
    {
        DB::transaction(function () use ($key, $id, $actor): void {
            $definition = $this->registry->definition($key);
            $class = $definition['model'];
            $model = $class::query()->lockForUpdate()->findOrFail($id);
            $this->guard->ensureCanRemove($key, $model);
            $this->ensureAccountCanBeDeleted($key, $model, $actor);
            $model->delete();
            $this->disableAccount($key, $model);
        });
    }

    public function restore(string $key, int $id, ?callable $scope = null): void
    {
        DB::transaction(function () use ($key, $id, $scope): void {
            $query = $this->registry->query($key);
            $scope?->__invoke($query);
            $model = $query->lockForUpdate()->findOrFail($id);
            if ($model->removed_from_trash_at !== null) {
                throw new ConflictHttpException(__('trash.cannot_restore_removed'));
            }
            $model->restore();
            $this->enableAccount($key, $model);
            if ($model instanceof Role) {
                app(PermissionRegistrar::class)->forgetCachedPermissions();
            }
        });
    }

    public function remove(string $key, int $id, ?User $actor = null, ?callable $scope = null): void
    {
        DB::transaction(function () use ($key, $id, $actor, $scope): void {
            $query = $this->registry->query($key);
            $scope?->__invoke($query);
            $model = $query->lockForUpdate()->findOrFail($id);
            $this->guard->ensureCanRemove($key, $model);
            $model->forceFill(['removed_from_trash_at' => now()])->saveQuietly();
            $this->recordRemoval($model, $actor);
            $this->disableAccount($key, $model);
            $this->releaseAccountIdentity($key, $model);
            if ($model instanceof Role) {
                app(PermissionRegistrar::class)->forgetCachedPermissions();
            }
        });
    }

    public function removeExpired(): int
    {
        $removed = 0;
        $cutoff = now()->subDays(max(1, $this->settings->retention_days));

        foreach (array_keys($this->registry->definitions()) as $key) {
            $this->registry->query($key)->where('deleted_at', '<=', $cutoff)->orderBy('id')->chunkById(100, function ($models) use ($key, &$removed): void {
                foreach ($models as $model) {
                    try {
                        $this->remove($key, (int) $model->getKey());
                        $removed++;
                    } catch (ConflictHttpException) {
                        // Linked records remain visible in Trash for manual resolution.
                    }
                }
            });
        }

        return $removed;
    }

    private function applyFilters(string $key, Builder $query, array $filters): void
    {
        if ($search = $filters['search'] ?? null) {
            $like = '%'.addcslashes($search, '%_\\').'%';
            $columns = $this->registry->definition($key)['search'];
            $query->where(function (Builder $nested) use ($columns, $like, $key): void {
                foreach ($columns as $column) {
                    $nested->orWhere($column, 'like', $like);
                }
                if (in_array($key, ['admins', 'clients', 'talents'], true)) {
                    $nested->orWhereHas('user', fn (Builder $user) => $user
                        ->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('email', 'like', $like));
                }
            });
        }
        if (array_key_exists('is_active', $filters)) {
            $query->where('is_active', (bool) $filters['is_active']);
        }
    }

    private function serialize(string $key, Model $model, ?array $availableActions): array
    {
        $event = TrashEvent::query()->with('actor')->where('resource_type', $model->trashResourceType())
            ->where('resource_id', $model->getKey())->where('action', 'deleted')->latest('id')->first();

        return [
            'id' => $model->getKey(),
            'type' => $key,
            'name' => $this->name($key, $model),
            'status' => $this->scalar($model->getAttribute('status')),
            'is_active' => $model->getAttribute('is_active'),
            'deleted_at' => $model->deleted_at?->toIso8601String(),
            'scheduled_removal_at' => $model->deleted_at?->copy()->addDays(max(1, $this->settings->retention_days))->toIso8601String(),
            'deleted_by' => $event?->actor ? [
                'id' => $event->actor->getKey(),
                'name' => trim($event->actor->first_name.' '.$event->actor->last_name),
            ] : null,
            'deletion_reason' => $event?->reason,
            'available_actions' => $availableActions ?? ['restore', 'remove_from_trash'],
            'data' => $this->details($key, $model),
        ];
    }

    private function name(string $key, Model $model): string
    {
        return match ($key) {
            'job-forms' => (string) $model->title,
            'form-submissions' => (string) $model->applicant_name,
            'admins', 'clients', 'talents' => trim(($model->user?->first_name ?? '').' '.($model->user?->last_name ?? '')),
            'roles' => (string) ($model->name_en ?: $model->name),
            'workspace-roles' => (string) ($model->name_en ?: $model->key),
            'help-requests' => (string) $model->reference,
            'workspaces' => (string) $model->name,
            'team' => trim(($model->client?->user?->first_name ?? '').' '.($model->client?->user?->last_name ?? '')),
            default => (string) ($model->name ?? $model->code ?? $model->slug ?? $model->getKey()),
        };
    }

    private function details(string $key, Model $model): array
    {
        return match ($key) {
            'form-submissions' => ['email' => $model->email, 'phone' => trim($model->phone_country_code.' '.$model->phone_number), 'submitted_at' => $model->submitted_at],
            'talents' => ['talent_code' => $model->talent_code, 'email' => $model->user?->email, 'application_status' => $this->scalar($model->applications->sortByDesc('id')->first()?->status)],
            'admins' => ['email' => $model->user?->email, 'phone' => trim($model->phone_country_code.' '.$model->phone_number), 'roles' => $model->user?->roles?->pluck('name')->values()],
            'clients' => ['email' => $model->user?->email, 'phone' => trim($model->phone_country_code.' '.$model->phone_number), 'workspaces_count' => $model->ownedWorkspaces()->withTrashed()->count()],
            'roles' => ['users_count' => DB::table('model_has_roles')->where('role_id', $model->getKey())->count()],
            'workspace-roles' => ['users_count' => DB::table('workspace_member_roles')->where('workspace_role_id', $model->getKey())->count()],
            'team' => ['email' => $model->client?->user?->email, 'phone' => trim(($model->client?->phone_country_code ?? '').' '.($model->client?->phone_number ?? '')), 'roles' => $model->roles->pluck('name_en')->values()],
            'workspaces' => ['industries' => $model->industries->pluck('name')->values(), 'professional_fields' => $model->professionalFields->pluck('name')->values()],
            'help-requests' => ['workspace' => $model->workspace?->name, 'help_type' => $this->scalar($model->help_type)],
            default => [],
        };
    }

    private function scalar(mixed $value): mixed
    {
        return $value instanceof BackedEnum ? $value->value : $value;
    }

    private function pagination(LengthAwarePaginator $paginator): array
    {
        return ['current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total()];
    }

    private function recordRemoval(Model $model, ?User $actor): void
    {
        $accountUser = in_array($model->trashResourceType(), ['Admin', 'Client', 'Talent'], true)
            ? $model->user
            : null;

        TrashEvent::query()->create([
            'resource_type' => $model->trashResourceType(), 'resource_id' => $model->getKey(),
            'action' => 'removed', 'actor_user_id' => $actor?->getKey(),
            'portal' => $this->scalar($actor?->type), 'workspace_id' => $model->getAttribute('workspace_id'),
            'reason' => null,
            'metadata' => $accountUser ? ['released_email' => $accountUser->email] : null,
            'created_at' => now(),
        ]);
    }

    private function ensureAccountCanBeDeleted(string $key, Model $model, ?User $actor): void
    {
        if ($key === 'admins') {
            if ($model->user_id === $actor?->getKey() || $model->user?->hasRole('super_admin')) {
                throw new ConflictHttpException(__('trash.protected_admin'));
            }
        }
    }

    private function disableAccount(string $key, Model $model): void
    {
        if (in_array($key, ['admins', 'clients', 'talents'], true) && $model->user) {
            $model->user->forceFill(['is_active' => false])->save();
            $model->user->tokens()->delete();
            $model->user->userDevices()->delete();
        }
    }

    private function enableAccount(string $key, Model $model): void
    {
        if (in_array($key, ['admins', 'clients', 'talents'], true) && $model->user) {
            $event = TrashEvent::query()
                ->where('resource_type', $model->trashResourceType())
                ->where('resource_id', $model->getKey())
                ->where('action', 'deleted')
                ->latest('id')
                ->first();

            $model->user->forceFill([
                'is_active' => (bool) data_get($event?->metadata, 'account_was_active', true),
            ])->save();
        }
    }

    private function releaseAccountIdentity(string $key, Model $model): void
    {
        if (! in_array($key, ['admins', 'clients', 'talents'], true) || ! $model->user) {
            return;
        }

        $user = $model->user;
        $suffix = $user->getKey().'-'.now()->format('YmdHis');

        $user->socialAccounts()->get()->each(function (Model $account) use ($suffix): void {
            $account->forceFill([
                'provider_user_id' => $account->provider_user_id.'-removed-'.$suffix,
                'email' => "removed-{$suffix}@trash.qader.invalid",
            ])->save();
        });
        $user->forceFill([
            'email' => "removed-{$suffix}@trash.qader.invalid",
            'is_active' => false,
        ])->save();
    }
}
