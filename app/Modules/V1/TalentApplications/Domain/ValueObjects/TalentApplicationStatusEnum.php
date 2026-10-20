<?php

namespace App\Modules\V1\TalentApplications\Domain\ValueObjects;

enum TalentApplicationStatusEnum: string
{
    case SUBMITTED = 'submitted';
    case UNDER_REVIEW = 'under_review';
    case INTERVIEW_REQUIRED = 'interview_required';
    case REJECTED = 'rejected';
    case ACCEPTED = 'accepted';
}
