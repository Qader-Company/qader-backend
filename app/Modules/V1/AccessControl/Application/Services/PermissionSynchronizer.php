<?php

namespace App\Modules\V1\AccessControl\Application\Services;

use App\Modules\V1\AccessControl\Domain\Models\Permission;
use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

class PermissionSynchronizer
{
    public function sync(): void
    {
        $this->forgetCachedPermissions();

        DB::transaction(function (): void {
            $this->migrateLegacyAdminPermissions();
            $this->normalizeRegisteredPermissionGuards();
            $this->upsertPermissions();
            $this->deletePermissionsOutsideRegistry();
        });

        $this->forgetCachedPermissions();
    }

    private function migrateLegacyAdminPermissions(): void
    {
        foreach (AdminPermissionEnum::cases() as $permission) {
            $legacyName = Str::after($permission->value, 'admin.');

            $legacyPermission = Permission::query()
                ->where('name', $legacyName)
                ->where('portal', PortalTypeEnum::ADMIN->value)
                ->first();

            if ($legacyPermission === null) {
                continue;
            }

            $currentPermission = Permission::query()
                ->where('name', $permission->value)
                ->where('portal', PortalTypeEnum::ADMIN->value)
                ->orderByRaw(
                    'guard_name = ? desc',
                    [PermissionRegistry::GUARD]
                )
                ->first();

            if ($currentPermission === null) {
                $legacyPermission->update([
                    'name' => $permission->value,
                ]);

                continue;
            }

            $this->movePermissionAssignments(
                sourcePermissionId: $legacyPermission->getKey(),
                targetPermissionId: $currentPermission->getKey()
            );

            $legacyPermission->delete();
        }
    }

    private function normalizeRegisteredPermissionGuards(): void
    {
        foreach (PermissionRegistry::permissions() as $definition) {
            $matches = Permission::query()
                ->where('name', $definition->value)
                ->where('portal', $definition->portal()->value)
                ->orderByRaw(
                    'guard_name = ? desc',
                    [PermissionRegistry::GUARD]
                )
                ->get();

            $permission = $matches->shift();

            if ($permission === null) {
                continue;
            }

            foreach ($matches as $duplicate) {
                $this->movePermissionAssignments(
                    $duplicate->getKey(),
                    $permission->getKey()
                );
                $duplicate->delete();
            }

            if ($permission->guard_name !== PermissionRegistry::GUARD) {
                $permission->forceFill([
                    'guard_name' => PermissionRegistry::GUARD,
                ])->save();
            }
        }
    }

    private function movePermissionAssignments(
        int $sourcePermissionId,
        int $targetPermissionId
    ): void {
        $roleAssignments = DB::table('role_has_permissions')
            ->where('permission_id', $sourcePermissionId)
            ->get(['role_id'])
            ->map(static fn (object $assignment): array => [
                'permission_id' => $targetPermissionId,
                'role_id' => $assignment->role_id,
            ])
            ->all();

        if ($roleAssignments !== []) {
            DB::table('role_has_permissions')->insertOrIgnore($roleAssignments);
        }

        $userAssignments = DB::table('model_has_permissions')
            ->where('permission_id', $sourcePermissionId)
            ->get(['model_type', 'model_id'])
            ->map(static fn (object $assignment): array => [
                'permission_id' => $targetPermissionId,
                'model_type' => $assignment->model_type,
                'model_id' => $assignment->model_id,
            ])
            ->all();

        if ($userAssignments !== []) {
            DB::table('model_has_permissions')->insertOrIgnore($userAssignments);
        }
    }

    private function upsertPermissions(): void
    {
        $timestamp = now();
        $records = array_map(
            static fn ($permission): array => [
                'name' => $permission->value,
                'guard_name' => PermissionRegistry::GUARD,
                'portal' => $permission->portal()->value,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            PermissionRegistry::permissions()
        );

        if ($records === []) {
            return;
        }

        Permission::query()->upsert(
            $records,
            ['name', 'guard_name', 'portal'],
            ['updated_at']
        );
    }

    private function deletePermissionsOutsideRegistry(): void
    {
        Permission::query()
            ->whereNotIn('portal', PortalTypeEnum::values())
            ->delete();

        foreach (PortalTypeEnum::cases() as $portal) {
            $registeredNames = PermissionRegistry::names($portal);
            $permissions = Permission::query()
                ->where('portal', $portal->value);

            if ($registeredNames === []) {
                $permissions->delete();

                continue;
            }

            $permissions
                ->where(function ($query) use ($registeredNames): void {
                    $query
                        ->whereNotIn('name', $registeredNames)
                        ->orWhere('guard_name', '!=', PermissionRegistry::GUARD);
                })
                ->delete();
        }
    }

    private function forgetCachedPermissions(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
