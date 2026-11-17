<?php

namespace App\Modules\V1\Clients\Domain\ValueObjects;

enum ClientRegistrationPeriodEnum: string
{
    case LAST_7_DAYS = 'last_7_days';
    case LAST_30_DAYS = 'last_30_days';
    case LAST_90_DAYS = 'last_90_days';
    case THIS_YEAR = 'this_year';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
