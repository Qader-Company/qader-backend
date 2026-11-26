<?php

namespace App\Modules\V1\Payments\Domain\ValueObjects;

enum PaymentAttemptStatusEnum: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case PAID = 'paid';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';

    public function isFinal(): bool
    {
        return in_array($this, [self::PAID, self::FAILED, self::CANCELLED, self::EXPIRED], true);
    }
}
