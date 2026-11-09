<?php

namespace App\Modules\V1\TalentApplications\Domain\ValueObjects;

enum TalentApplicationStatusEnum: string
{
    case SUBMITTED = 'submitted';
    case UNDER_REVIEW = 'under_review';
    case INTERVIEW_REQUIRED = 'interview_required';
    case SCHEDULED = 'scheduled';
    case REJECTED = 'rejected';
    case ACCEPTED = 'accepted';

    /**
     * The application is in the interview stage, whether a slot is still pending (interview_required)
     * or an interview time has been confirmed (scheduled).
     */
    public function isInterviewStage(): bool
    {
        return $this === self::INTERVIEW_REQUIRED || $this === self::SCHEDULED;
    }
}
