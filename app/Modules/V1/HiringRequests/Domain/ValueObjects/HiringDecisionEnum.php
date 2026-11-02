<?php

namespace App\Modules\V1\HiringRequests\Domain\ValueObjects;

enum HiringDecisionEnum: string
{
    case PROCEED = 'proceed';
    case REJECT = 'reject';
}
