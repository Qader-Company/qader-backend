<?php

namespace App\Modules\V1\AccessControl\Domain\ValueObjects;

use App\Modules\V1\AccessControl\Domain\ValueObjects\Concerns\InteractsWithPermissions;
use App\Modules\V1\AccessControl\Domain\ValueObjects\Contracts\PermissionDefinition;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;

enum TalentPermissionEnum: string implements PermissionDefinition
{
    use InteractsWithPermissions;

    case PLATFORM_ACCESS = 'talent.platform.access';

    public function portal(): PortalTypeEnum
    {
        return PortalTypeEnum::TALENT;
    }
}
