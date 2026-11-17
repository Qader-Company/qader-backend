<?php

namespace App\Modules\V1\Clients\Domain\ValueObjects;

enum ClientListSortEnum: string
{
    case NEWEST = 'newest';
    case OLDEST = 'oldest';
    case NAME_ASC = 'name_asc';
    case NAME_DESC = 'name_desc';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
