<?php

namespace App\Modules\V1\TalentApplications\Domain\ValueObjects;

enum EvaluationTipPriorityEnum: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
}
