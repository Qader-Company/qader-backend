<?php

namespace App\Modules\V1\TalentApplications\Domain\ValueObjects;

enum InterviewStatusEnum: string
{
    case AWAITING_SLOT_SELECTION = 'awaiting_slot_selection';
}
