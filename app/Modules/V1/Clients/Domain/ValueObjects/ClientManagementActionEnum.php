<?php

namespace App\Modules\V1\Clients\Domain\ValueObjects;

enum ClientManagementActionEnum: string
{
    case CLIENT_SUSPENDED = 'client_suspended';
    case CLIENT_REACTIVATED = 'client_reactivated';
    case CLIENT_DELETED = 'client_deleted';
    case WORKSPACE_ACTIVATED = 'workspace_activated';
    case WORKSPACE_DEACTIVATED = 'workspace_deactivated';
    case WORKSPACE_DELETED = 'workspace_deleted';
    case WORKSPACE_MEMBER_DELETED = 'workspace_member_deleted';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
