<?php

namespace App\Modules\V1\HiringRequests\Domain\ValueObjects;

enum HiringInterviewResponseEnum: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case CHANGE_REQUESTED = 'change_requested';
    case APOLOGIZED = 'apologized';
}
