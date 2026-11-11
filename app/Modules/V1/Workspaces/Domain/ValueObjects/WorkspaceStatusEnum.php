<?php

namespace App\Modules\V1\Workspaces\Domain\ValueObjects;

enum WorkspaceStatusEnum: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
