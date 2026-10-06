<?php

namespace App\Modules\V1\Talents\Domain\ValueObjects;

enum WorkModeEnum: string
{
    case REMOTE = 'remote';
    case ONSITE = 'onsite';
    case HYBRID = 'hybrid';
}
