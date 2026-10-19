<?php

namespace App\Modules\V1\AccessControl\Infrastructure\Persistence\Repositories;

use App\Modules\V1\AccessControl\Domain\Models\Role;
use App\Modules\V1\AccessControl\Domain\Repositories\RoleRepositoryInterface;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentRoleRepository implements RoleRepositoryInterface
{
    public function list(PortalTypeEnum $portal, array $filters = []): Collection
    {
        $query = DB::table('roles')
            ->where('portal', $portal->value)
            ->when(
                $filters['search'] ?? null,
                fn ($query, string $search) => $query->where(
                    fn ($query) => $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('name_en', 'like', "%{$search}%")
                        ->orWhere('name_ar', 'like', "%{$search}%")
                )
            )
            ->when(
                array_key_exists('is_active', $filters),
                fn ($query) => $query->where(
                    'is_active',
                    $filters['is_active']
                )
            )
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'name_en',
                'name_ar',
                'portal',
                'is_active',
            ]);

        $roles = $query;
        $this->attachUserCounts($roles);

        return $this->attachPermissions($roles, $portal);
    }

    public function findOrFail(
        int $roleId,
        PortalTypeEnum $portal
    ): object {
        return $this->find($roleId, $portal);
    }

    public function create(
        array $attributes,
        array $permissions
    ): object {
        $role = Role::query()->create($attributes);
        $role->syncPermissions($permissions);

        return $this->findOrFail(
            $role->getKey(),
            PortalTypeEnum::from($attributes['portal'])
        );
    }

    public function update(
        int $roleId,
        PortalTypeEnum $portal,
        array $attributes,
        ?array $permissions
    ): object {
        $role = $this->findModelOrFail($roleId, $portal);

        $role->update($attributes);
        if ($permissions !== null) {
            $role->syncPermissions($permissions);
        }

        return $this->find($roleId, $portal);
    }

    public function updateStatus(
        int $roleId,
        PortalTypeEnum $portal,
        bool $isActive
    ): object {
        $this->findModelOrFail($roleId, $portal)->update([
            'is_active' => $isActive,
        ]);

        return $this->find($roleId, $portal);
    }

    public function delete(
        int $roleId,
        PortalTypeEnum $portal
    ): void {
        $this->findModelOrFail($roleId, $portal)->delete();
    }

    public function isAssigned(
        int $roleId,
        PortalTypeEnum $portal
    ): bool {
        $this->findModelOrFail($roleId, $portal);

        return DB::table('model_has_roles')
            ->where('role_id', $roleId)
            ->exists();
    }

    private function find(
        int $roleId,
        PortalTypeEnum $portal
    ): object {
        $role = DB::table('roles')
            ->where('id', $roleId)
            ->where('portal', $portal->value)
            ->first([
                'id',
                'name',
                'name_en',
                'name_ar',
                'portal',
                'is_active',
            ]);

        if ($role === null) {
            throw (new ModelNotFoundException)->setModel(
                Role::class,
                [$roleId]
            );
        }

        $roles = collect([$role]);
        $this->attachUserCounts($roles);

        return $this->attachPermissions($roles, $portal)->first();
    }

    private function findModelOrFail(
        int $roleId,
        PortalTypeEnum $portal
    ): Role {
        return Role::query()
            ->whereKey($roleId)
            ->where('portal', $portal->value)
            ->firstOrFail();
    }

    private function attachPermissions(
        Collection $roles,
        PortalTypeEnum $portal
    ): Collection {
        $roleIds = $roles->pluck('id');

        if ($roleIds->isEmpty()) {
            return $roles;
        }

        $permissionsByRole = DB::table('role_has_permissions')
            ->join(
                'permissions',
                'permissions.id',
                '=',
                'role_has_permissions.permission_id'
            )
            ->whereIn('role_has_permissions.role_id', $roleIds)
            ->where('permissions.portal', $portal->value)
            ->orderBy('permissions.name')
            ->get([
                'role_has_permissions.role_id',
                'permissions.id',
                'permissions.name',
                'permissions.portal',
            ])
            ->groupBy('role_id');

        return $roles->each(
            function (object $role) use ($permissionsByRole): void {
                $role->permissions = $permissionsByRole
                    ->get($role->id, collect())
                    ->values();
            }
        );
    }

    private function attachUserCounts(Collection $roles): void
    {
        $counts = DB::table('model_has_roles')
            ->whereIn('role_id', $roles->pluck('id'))
            ->selectRaw('role_id, COUNT(*) as users_count')
            ->groupBy('role_id')
            ->pluck('users_count', 'role_id');

        $roles->each(function (object $role) use ($counts): void {
            $role->users_count = (int) ($counts[$role->id] ?? 0);
        });
    }
}
