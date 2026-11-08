<?php

namespace App\Modules\V1\TalentApplications\Domain\ValueObjects;

enum InterviewSlotStatusEnum: string
{
    case AVAILABLE = 'available';
    case SELECTED = 'selected';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';
    case REJECTED_BY_TALENT = 'rejected_by_talent';

    /** @return array<int, string> */
    public static function blockingValues(): array
    {
        return [self::AVAILABLE->value, self::SELECTED->value];
    }
}
