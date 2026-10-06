<?php

namespace App\Modules\V1\AccessControl\Application\Services;

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\AccessControl\Domain\ValueObjects\ClientPermissionEnum;
use App\Modules\V1\AccessControl\Domain\ValueObjects\Contracts\PermissionDefinition;
use App\Modules\V1\AccessControl\Domain\ValueObjects\TalentPermissionEnum;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;

class PermissionRegistry
{
    public const GUARD = 'web';

    /**
     * @return class-string<PermissionDefinition>
     */
    public static function enumFor(PortalTypeEnum $portal): string
    {
        return match ($portal) {
            PortalTypeEnum::ADMIN => AdminPermissionEnum::class,
            PortalTypeEnum::TALENT => TalentPermissionEnum::class,
            PortalTypeEnum::CLIENT => ClientPermissionEnum::class,
        };
    }

    /** @return array<int, PermissionDefinition> */
    public static function permissions(?PortalTypeEnum $portal = null): array
    {
        if ($portal !== null) {
            $permissionEnum = self::enumFor($portal);

            return $permissionEnum::cases();
        }

        $permissions = [];

        foreach (PortalTypeEnum::cases() as $permissionPortal) {
            $permissions = [
                ...$permissions,
                ...self::permissions($permissionPortal),
            ];
        }

        return $permissions;
    }

    /** @return array<int, string> */
    public static function names(PortalTypeEnum $portal): array
    {
        return array_map(
            static fn (PermissionDefinition $permission): string => $permission->value,
            self::permissions($portal)
        );
    }

    public static function find(PortalTypeEnum $portal, string $name): ?PermissionDefinition
    {
        $permissionEnum = self::enumFor($portal);

        return $permissionEnum::tryFrom($name);
    }
}
