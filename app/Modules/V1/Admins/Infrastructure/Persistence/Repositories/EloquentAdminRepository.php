<?php

namespace App\Modules\V1\Admins\Infrastructure\Persistence\Repositories;

use App\Modules\V1\AccessControl\Domain\Models\Role;
use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminRoleEnum;
use App\Modules\V1\Admins\Domain\Models\Admin;
use App\Modules\V1\Admins\Domain\Repositories\AdminRepositoryInterface;
use App\Modules\V1\Admins\Infrastructure\Filters\AdminFilter;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Arr;
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
            ...Arr::only($attributes, $this->userAttributes()),
            'type' => PortalTypeEnum::ADMIN,
            'email_verified_at' => now(),
        ]);

        $admin = Admin::query()->create([
            'user_id' => $user->getKey(),
            ...Arr::only($attributes, $this->adminAttributes()),
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

        $user->update(Arr::only($attributes, $this->userAttributes()));
        $admin->update(Arr::only($attributes, $this->adminAttributes()));

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

    public function hasSuperAdminRole(int $adminId): bool
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
            ->where('roles.name', AdminRoleEnum::SUPER_ADMIN->value)
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
                'admins.display_name_ar',
                'admins.phone_country_code',
                'admins.phone_number',
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
                'roles.name_en',
                'roles.name_ar',
                'roles.is_active',
            ])
            ->groupBy('user_id');

        $admins->each(function (object $admin) use ($rolesByUser): void {
            $admin->roles = $rolesByUser
                ->get($admin->user_id, collect())
                ->map(static fn (object $role): array => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'name_en' => $role->name_en,
                    'name_ar' => $role->name_ar,
                    'is_active' => (bool) $role->is_active,
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
            ->where('name', '!=', AdminRoleEnum::SUPER_ADMIN->value)
            ->where('is_active', true)
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

    /** @return array<int, string> */
    private function userAttributes(): array
    {
        return [
            'first_name', 'last_name', 'email', 'password', 'avatar',
            'is_active',
        ];
    }

    /** @return array<int, string> */
    private function adminAttributes(): array
    {
        return ['display_name_ar', 'phone_country_code', 'phone_number'];
    }
}
