<?php

namespace App\Modules\V1\Clients\Domain\ValueObjects;

enum ClientAccountStatusEnum: string
{
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
