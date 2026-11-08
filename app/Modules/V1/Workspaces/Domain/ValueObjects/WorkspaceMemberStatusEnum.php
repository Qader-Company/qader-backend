<?php

namespace App\Modules\V1\Workspaces\Domain\ValueObjects;

enum WorkspaceMemberStatusEnum: string
{
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
