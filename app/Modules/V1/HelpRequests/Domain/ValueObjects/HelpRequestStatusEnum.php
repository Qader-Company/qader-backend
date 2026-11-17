<?php

namespace App\Modules\V1\HelpRequests\Domain\ValueObjects;

enum HelpRequestStatusEnum: string
{
    case NEW = 'new';
    case IN_PROGRESS = 'in_progress';
    case CLOSED = 'closed';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
