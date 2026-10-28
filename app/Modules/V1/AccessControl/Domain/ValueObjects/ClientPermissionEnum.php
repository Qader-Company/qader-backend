<?php

namespace App\Modules\V1\AccessControl\Domain\ValueObjects;

use App\Modules\V1\AccessControl\Domain\ValueObjects\Concerns\InteractsWithPermissions;
use App\Modules\V1\AccessControl\Domain\ValueObjects\Contracts\PermissionDefinition;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;

enum ClientPermissionEnum: string implements PermissionDefinition
{
    use InteractsWithPermissions;

    case WORKSPACE_VIEW = 'client.workspace.view';
    case WORKSPACE_UPDATE = 'client.workspace.update';
    case DASHBOARD_VIEW = 'client.dashboard.view';
    case MEMBERS_VIEW = 'client.members.view';
    case MEMBERS_CREATE = 'client.members.create';
    case MEMBERS_MANAGE = 'client.members.manage';
    case ROLES_VIEW = 'client.roles.view';
    case ROLES_MANAGE = 'client.roles.manage';
    case MARKETPLACE_VIEW = 'client.marketplace.view';
    case TALENT_SELECTIONS_VIEW = 'client.talent-selections.view';
    case TALENT_SELECTIONS_MANAGE = 'client.talent-selections.manage';

    public function portal(): PortalTypeEnum
    {
        return PortalTypeEnum::CLIENT;
    }
}
