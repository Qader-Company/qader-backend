<?php

namespace App\Modules\V1\Workspaces\Domain\ValueObjects;

enum WorkspaceSizeBandEnum: string
{
    case ONE_TO_TEN = '1-10';
    case ELEVEN_TO_FIFTY = '11-50';
    case FIFTY_ONE_TO_TWO_HUNDRED = '51-200';
    case TWO_HUNDRED_ONE_TO_FIVE_HUNDRED = '201-500';
    case FIVE_HUNDRED_PLUS = '500+';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
