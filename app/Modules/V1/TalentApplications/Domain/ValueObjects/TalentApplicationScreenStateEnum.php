<?php

namespace App\Modules\V1\TalentApplications\Domain\ValueObjects;

enum TalentApplicationScreenStateEnum: string
{
    case UNDER_REVIEW = 'under_review';
    case REJECTED = 'rejected';
    case INTERVIEW_SLOT_REQUIRED = 'interview_slot_required';
    case AWAITING_NEW_INTERVIEW_SLOTS = 'awaiting_new_interview_slots';
    case INTERVIEW_CONFIRMED = 'interview_confirmed';
    case INTERVIEW_IN_PROGRESS = 'interview_in_progress';
    case INTERVIEW_COMPLETED = 'interview_completed';
    case INTERVIEW_CANCELLED = 'interview_cancelled';
    case ACCEPTED = 'accepted';
    case BLACKLISTED = 'blacklisted';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
