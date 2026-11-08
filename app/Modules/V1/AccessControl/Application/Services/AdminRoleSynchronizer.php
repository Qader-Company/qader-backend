<?php

namespace App\Modules\V1\AccessControl\Application\Services;

use App\Modules\V1\AccessControl\Domain\Models\Role;
use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminRoleEnum;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Support\Facades\DB;

class AdminRoleSynchronizer
{
    /** @return array<string, Role> */
    public function sync(): array
    {
        return DB::transaction(function (): array {
            $this->migrateLegacyOwnerRole();

            $role = $this->upsertRole(
                AdminRoleEnum::SUPER_ADMIN->value,
                AdminRoleEnum::SUPER_ADMIN->nameEn(),
                AdminRoleEnum::SUPER_ADMIN->nameAr(),
                PermissionRegistry::names(PortalTypeEnum::ADMIN)
            );

            return [AdminRoleEnum::SUPER_ADMIN->value => $role];
        });
    }

    /** @param array<int, string> $permissions */
    public function syncSeededRole(
        string $name,
        string $nameEn,
        string $nameAr,
        array $permissions
    ): Role {
        return DB::transaction(fn (): Role => $this->upsertRole(
            $name,
            $nameEn,
            $nameAr,
            $permissions
        ));
    }

    private function migrateLegacyOwnerRole(): void
    {
        $legacy = Role::query()
            ->where('name', 'owner')
            ->where('portal', PortalTypeEnum::ADMIN->value)
            ->get();

        if ($legacy->isEmpty()) {
            return;
        }

        $superAdmin = Role::query()
            ->where('name', AdminRoleEnum::SUPER_ADMIN->value)
            ->where('portal', PortalTypeEnum::ADMIN->value)
            ->orderByRaw('guard_name = ? desc', [PermissionRegistry::GUARD])
            ->first();

        if ($superAdmin === null) {
            $superAdmin = $legacy->shift();
            $superAdmin->forceFill([
                'name' => AdminRoleEnum::SUPER_ADMIN->value,
                'name_en' => AdminRoleEnum::SUPER_ADMIN->nameEn(),
                'name_ar' => AdminRoleEnum::SUPER_ADMIN->nameAr(),
                'guard_name' => PermissionRegistry::GUARD,
                'is_active' => true,
            ])->save();
        }

        foreach ($legacy as $legacyRole) {
            $this->moveRoleAssignments(
                $legacyRole->getKey(),
                $superAdmin->getKey()
            );
            $legacyRole->delete();
        }
    }

    /** @param array<int, string> $permissions */
    private function upsertRole(
        string $name,
        string $nameEn,
        string $nameAr,
        array $permissions
    ): Role {
        $matches = Role::query()
            ->where('name', $name)
            ->where('portal', PortalTypeEnum::ADMIN->value)
            ->orderByRaw('guard_name = ? desc', [PermissionRegistry::GUARD])
            ->get();

        $role = $matches->shift();

        if ($role === null) {
            $role = new Role;
            $role->name = $name;
            $role->portal = PortalTypeEnum::ADMIN->value;
        }

        foreach ($matches as $duplicate) {
            $this->moveRoleAssignments(
                $duplicate->getKey(),
                $role->getKey()
            );
            $duplicate->delete();
        }

        $role->forceFill([
            'name_en' => $nameEn,
            'name_ar' => $nameAr,
            'guard_name' => PermissionRegistry::GUARD,
            'is_active' => true,
        ])->save();

        $role->syncPermissions($permissions);

        return $role->refresh();
    }

    private function moveRoleAssignments(int $sourceRoleId, int $targetRoleId): void
    {
        $modelAssignments = DB::table('model_has_roles')
            ->where('role_id', $sourceRoleId)
            ->get(['model_type', 'model_id'])
            ->map(static fn (object $assignment): array => [
                'role_id' => $targetRoleId,
                'model_type' => $assignment->model_type,
                'model_id' => $assignment->model_id,
            ])
            ->all();

        if ($modelAssignments !== []) {
            DB::table('model_has_roles')->insertOrIgnore($modelAssignments);
        }

        $permissionAssignments = DB::table('role_has_permissions')
            ->where('role_id', $sourceRoleId)
            ->get(['permission_id'])
            ->map(static fn (object $assignment): array => [
                'permission_id' => $assignment->permission_id,
                'role_id' => $targetRoleId,
            ])
            ->all();

        if ($permissionAssignments !== []) {
            DB::table('role_has_permissions')->insertOrIgnore(
                $permissionAssignments
            );
        }

        DB::table('model_has_roles')
            ->where('role_id', $sourceRoleId)
            ->delete();

        DB::table('role_has_permissions')
            ->where('role_id', $sourceRoleId)
            ->delete();
    }
}
