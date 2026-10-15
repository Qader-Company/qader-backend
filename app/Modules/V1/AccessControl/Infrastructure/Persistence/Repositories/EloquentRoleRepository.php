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
    public function list(PortalTypeEnum $portal): Collection
    {
        $roles = DB::table('roles')
            ->where('portal', $portal->value)
            ->where('name', '!=', 'owner')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'portal',
            ]);

        return $this->attachPermissions($roles, $portal);
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
        array $permissions
    ): object {
        $role = $this->findModelOrFail($roleId, $portal);

        $role->update($attributes);
        $role->syncPermissions($permissions);

        return $this->findOrFail($roleId, $portal);
    }

    public function delete(
        int $roleId,
        PortalTypeEnum $portal
    ): void {
        $this->findModelOrFail($roleId, $portal)->delete();
    }

    public function hasName(
        int $roleId,
        PortalTypeEnum $portal,
        string $name
    ): bool {
        return DB::table('roles')
            ->where('id', $roleId)
            ->where('portal', $portal->value)
            ->where('name', $name)
            ->exists();
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

    private function findOrFail(
        int $roleId,
        PortalTypeEnum $portal
    ): object {
        $role = DB::table('roles')
            ->where('id', $roleId)
            ->where('portal', $portal->value)
            ->first([
                'id',
                'name',
                'portal',
            ]);

        if ($role === null) {
            throw (new ModelNotFoundException)->setModel(
                Role::class,
                [$roleId]
            );
        }

        return $this->attachPermissions(
            collect([$role]),
            $portal
        )->first();
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
}
