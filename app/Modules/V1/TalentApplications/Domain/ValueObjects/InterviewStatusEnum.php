<?php

namespace App\Modules\V1\TalentApplications\Domain\ValueObjects;

enum InterviewStatusEnum: string
{
    case AWAITING_SLOT_SELECTION = 'awaiting_slot_selection';
    case AWAITING_NEW_SLOTS = 'awaiting_new_slots';
    case CONFIRMED = 'confirmed';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
