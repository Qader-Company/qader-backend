<?php

namespace App\Modules\V1\Talents\Domain\ValueObjects;

enum OnboardingStepStatusEnum: string
{
    case NOT_STARTED = 'not_started';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
}
