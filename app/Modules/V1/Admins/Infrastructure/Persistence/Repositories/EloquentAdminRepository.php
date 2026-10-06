<?php

namespace App\Modules\V1\Admins\Infrastructure\Persistence\Repositories;

use App\Modules\V1\AccessControl\Domain\Models\Role;
use App\Modules\V1\Admins\Domain\Models\Admin;
use App\Modules\V1\Admins\Domain\Repositories\AdminRepositoryInterface;
use App\Modules\V1\Admins\Infrastructure\Filters\AdminFilter;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EloquentAdminRepository implements AdminRepositoryInterface
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $filteredAdmins = Admin::filter($filters, AdminFilter::class)
            ->select('admins.id');

        $admins = $this->query()
            ->whereIn('admins.id', $filteredAdmins)
            ->whereNotExists(function (Builder $query): void {
                $query
                    ->selectRaw('1')
                    ->from('model_has_roles as owner_roles')
                    ->join(
                        'roles as owner_role_definitions',
                        'owner_role_definitions.id',
                        '=',
                        'owner_roles.role_id'
                    )
                    ->whereColumn('owner_roles.model_id', 'users.id')
                    ->where(
                        'owner_roles.model_type',
                        $this->userMorphClass()
                    )
                    ->where(
                        'owner_role_definitions.portal',
                        PortalTypeEnum::ADMIN->value
                    )
                    ->where('owner_role_definitions.name', 'owner');
            })
            ->orderBy('users.first_name')
            ->orderBy('users.last_name')
            ->paginate($filters['per_page'] ?? 20);

        $this->attachRoles($admins->getCollection());

        return $admins;
    }

    public function findOrFail(int $adminId): object
    {
        $admin = $this->query()
            ->where('admins.id', $adminId)
            ->first();

        if ($admin === null) {
            throw (new ModelNotFoundException)->setModel(
                Admin::class,
                [$adminId]
            );
        }

        $this->attachRoles(collect([$admin]));

        return $admin;
    }

    public function create(array $attributes, array $roleIds): object
    {
        $user = User::query()->create([
            ...$attributes,
            'type' => PortalTypeEnum::ADMIN,
            'email_verified_at' => now(),
        ]);

        $admin = Admin::query()->create([
            'user_id' => $user->getKey(),
        ]);

        $this->syncRoles($user, $roleIds);

        return $this->findOrFail($admin->getKey());
    }

    public function update(
        int $adminId,
        array $attributes,
        ?array $roleIds = null
    ): object {
        $admin = Admin::query()->findOrFail($adminId);
        $user = User::query()->findOrFail($admin->user_id);

        $user->update($attributes);

        if ($roleIds !== null) {
            $this->syncRoles($user, $roleIds);
        }

        return $this->findOrFail($adminId);
    }

    public function updateStatus(int $adminId, bool $isActive): object
    {
        $admin = Admin::query()->findOrFail($adminId);

        User::query()
            ->whereKey($admin->user_id)
            ->update([
                'is_active' => $isActive,
            ]);

        return $this->findOrFail($adminId);
    }

    public function hasOwnerRole(int $adminId): bool
    {
        return DB::table('admins')
            ->join(
                'model_has_roles',
                'model_has_roles.model_id',
                '=',
                'admins.user_id'
            )
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('admins.id', $adminId)
            ->where('model_has_roles.model_type', $this->userMorphClass())
            ->where('roles.portal', PortalTypeEnum::ADMIN->value)
            ->where('roles.name', 'owner')
            ->exists();
    }

    public function belongsToUser(int $adminId, int $userId): bool
    {
        return DB::table('admins')
            ->where('id', $adminId)
            ->where('user_id', $userId)
            ->exists();
    }

    private function query(): Builder
    {
        return DB::table('admins')
            ->join('users', 'users.id', '=', 'admins.user_id')
            ->where('users.type', PortalTypeEnum::ADMIN->value)
            ->select([
                'admins.id',
                'users.id as user_id',
                'users.first_name',
                'users.last_name',
                'users.email',
                'users.avatar',
                'users.is_active',
                'users.email_verified_at',
                'users.last_login_at',
                'users.created_at',
                'users.updated_at',
            ]);
    }

    private function attachRoles(Collection $admins): void
    {
        $userIds = $admins
            ->pluck('user_id')
            ->filter()
            ->values();

        if ($userIds->isEmpty()) {
            return;
        }

        $rolesByUser = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_type', $this->userMorphClass())
            ->whereIn('model_has_roles.model_id', $userIds)
            ->where('roles.portal', PortalTypeEnum::ADMIN->value)
            ->orderBy('roles.name')
            ->get([
                'model_has_roles.model_id as user_id',
                'roles.id',
                'roles.name',
            ])
            ->groupBy('user_id');

        $admins->each(function (object $admin) use ($rolesByUser): void {
            $admin->roles = $rolesByUser
                ->get($admin->user_id, collect())
                ->map(static fn (object $role): array => [
                    'id' => $role->id,
                    'name' => $role->name,
                ])
                ->values()
                ->all();
        });
    }

    private function syncRoles(User $user, array $roleIds): void
    {
        $roles = Role::query()
            ->whereIn('id', $roleIds)
            ->where('portal', PortalTypeEnum::ADMIN->value)
            ->where('name', '!=', 'owner')
            ->get();

        if (
            $roles->isEmpty()
            || $roles->count() !== count(array_unique($roleIds))
        ) {
            throw new InvalidArgumentException(
                __('admins.invalid_roles')
            );
        }

        $user->syncRoles($roles);
    }

    private function userMorphClass(): string
    {
        return (new User)->getMorphClass();
    }
}
