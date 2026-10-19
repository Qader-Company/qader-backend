<?php

namespace App\Modules\V1\Users\Application\Services;

use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use BackedEnum;
use Illuminate\Support\Facades\DB;

class UserAccessResolver
{
    /** @return array{roles: array<int, string>, permissions: array<int, string>} */
    public static function resolve(User $user): array
    {
        $portal = self::resolvePortal($user);

        if ($portal === null) {
            return [
                'roles' => [],
                'permissions' => [],
            ];
        }

        $modelType = $user->getMorphClass();

        $roles = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_type', $modelType)
            ->where('model_has_roles.model_id', $user->getKey())
            ->where('roles.portal', $portal->value)
            ->where('roles.is_active', true)
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
            ->where('roles.is_active', true)
            ->where('permissions.portal', $portal->value)
            ->orderBy('permissions.name')
            ->pluck('permissions.name')
            ->unique()
            ->values()
            ->all();

        $directPermissions = DB::table('model_has_permissions')
            ->join(
                'permissions',
                'permissions.id',
                '=',
                'model_has_permissions.permission_id'
            )
            ->where('model_has_permissions.model_type', $modelType)
            ->where('model_has_permissions.model_id', $user->getKey())
            ->where('permissions.portal', $portal->value)
            ->pluck('permissions.name')
            ->all();

        $permissions = array_values(array_unique([
            ...$assignedPermissions,
            ...$directPermissions,
        ]));
        sort($permissions);

        return [
            'roles' => $roles,
            'permissions' => $permissions,
        ];
    }

    /** @param array<int, string> $permissions */
    public static function hasAnyPermission(User $user, array $permissions): bool
    {
        return array_intersect(
            self::resolve($user)['permissions'],
            $permissions
        ) !== [];
    }

    /** @param array<int, string> $roles */
    public static function hasAnyRole(User $user, array $roles): bool
    {
        return array_intersect(self::resolve($user)['roles'], $roles) !== [];
    }

    private static function resolvePortal(User $user): ?PortalTypeEnum
    {
        $userType = $user->type instanceof BackedEnum
            ? $user->type->value
            : $user->type;

        return PortalTypeEnum::tryFrom((string) $userType);
    }
}
