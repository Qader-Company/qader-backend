<?php

namespace App\Modules\V1\HiringRequests\Domain\ValueObjects;

enum HiringRequestItemStatusEnum: string
{
    case PENDING = 'pending';
    case OFFER = 'offer';
    case INTERVIEW = 'interview';
    case ACCEPTED = 'accepted';
    case ON_HOLD = 'on_hold';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';

    public function isFinal(): bool
    {
        return in_array($this, [self::REJECTED, self::CANCELLED, self::COMPLETED], true);
    }
}
