<?php

namespace App\Modules\V1\HelpRequests\Domain\ValueObjects;

enum HelpRequestListScopeEnum: string
{
    case ALL = 'all';
    case MINE = 'mine';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
