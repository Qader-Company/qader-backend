<?php

namespace App\Modules\V1\Users\Application\Services;

use App\Modules\V1\AccessControl\Application\Services\PermissionRegistry;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use BackedEnum;
use Illuminate\Support\Facades\DB;

class UserAccessResolver
{
    /** @return array{roles: array<int, string>, unassigned_permissions: array<int, string>} */
    public static function resolve(User $user): array
    {
        $portal = self::resolvePortal($user);

        if ($portal === null) {
            return [
                'roles' => [],
                'unassigned_permissions' => [],
            ];
        }

        $modelType = $user->getMorphClass();

        $roles = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_type', $modelType)
            ->where('model_has_roles.model_id', $user->getKey())
            ->where('roles.portal', $portal->value)
            ->orderBy('roles.name')
            ->pluck('roles.name')
            ->unique()
            ->values()
            ->all();

        $assignedPermissions = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->join(
                'role_has_permissions',
                'role_has_permissions.role_id',
                '=',
                'roles.id'
            )
            ->join(
                'permissions',
                'permissions.id',
                '=',
                'role_has_permissions.permission_id'
            )
            ->where('model_has_roles.model_type', $modelType)
            ->where('model_has_roles.model_id', $user->getKey())
            ->where('roles.portal', $portal->value)
            ->where('permissions.portal', $portal->value)
            ->orderBy('permissions.name')
            ->pluck('permissions.name')
            ->unique()
            ->values()
            ->all();

        $availablePermissions = array_values(array_diff(
            PermissionRegistry::names($portal),
            $assignedPermissions
        ));

        sort($availablePermissions);

        return [
            'roles' => $roles,
            'unassigned_permissions' => $availablePermissions,
        ];
    }

    private static function resolvePortal(User $user): ?PortalTypeEnum
    {
        $userType = $user->type instanceof BackedEnum
            ? $user->type->value
            : $user->type;

        return PortalTypeEnum::tryFrom((string) $userType);
    }
}
