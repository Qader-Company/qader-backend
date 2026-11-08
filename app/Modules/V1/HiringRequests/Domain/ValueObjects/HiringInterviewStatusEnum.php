<?php

namespace App\Modules\V1\HiringRequests\Domain\ValueObjects;

enum HiringInterviewStatusEnum: string
{
    case AWAITING_RESPONSES = 'awaiting_responses';
    case AWAITING_ADMIN_CONFIRMATION = 'awaiting_admin_confirmation';
    case AWAITING_RESCHEDULE = 'awaiting_reschedule';
    case CONFIRMED = 'confirmed';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
