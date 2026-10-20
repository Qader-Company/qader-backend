<?php

namespace App\Modules\V1\TalentApplications\Domain\ValueObjects;

enum InterviewModeEnum: string
{
    case ONLINE = 'online';
    case OFFLINE = 'offline';
}
