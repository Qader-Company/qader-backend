<?php

namespace App\Modules\V1\TalentApplications\Domain\ValueObjects;

enum InterviewTypeEnum: string
{
    case HR = 'hr';
    case TECHNICAL = 'technical';
    case GENERAL = 'general';
    case FINAL = 'final';
}
