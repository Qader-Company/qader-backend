<?php

namespace App\Modules\V1\HelpRequests\Domain\ValueObjects;

enum HelpRequestContactMethodEnum: string
{
    case EMAIL = 'email';
    case WHATSAPP = 'whatsapp';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
