<?php

namespace App\Modules\V1\AccessControl\Domain\ValueObjects;

use App\Modules\V1\AccessControl\Domain\ValueObjects\Concerns\InteractsWithPermissions;
use App\Modules\V1\AccessControl\Domain\ValueObjects\Contracts\PermissionDefinition;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;

enum ClientPermissionEnum: string implements PermissionDefinition
{
    use InteractsWithPermissions;

    public function portal(): PortalTypeEnum
    {
        return PortalTypeEnum::CLIENT;
    }
}
