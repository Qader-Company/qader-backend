<?php

namespace App\Modules\V1\HiringRequests\Domain\ValueObjects;

enum HiringRequestItemStatusEnum: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case AWAITING_TALENT_RESPONSE = 'awaiting_talent_response';
    case NEGOTIATING = 'negotiating';
    case AWAITING_INTERVIEW_SLOTS = 'awaiting_interview_slots';
    case INTERVIEW_SCHEDULED = 'interview_scheduled';
    case AWAITING_DECISIONS = 'awaiting_decisions';
    case READY_TO_PROCEED = 'ready_to_proceed';
    case READY_FOR_CONTRACT = 'ready_for_contract';
    case ON_HOLD = 'on_hold';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';

    public function isFinal(): bool
    {
        return in_array($this, [self::REJECTED, self::CANCELLED, self::COMPLETED], true);
    }
}
