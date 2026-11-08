<?php

namespace App\Modules\V1\JobForms\Domain\ValueObjects;

enum JobWorkModeEnum: string
{
    case ONSITE = 'onsite';
    case REMOTE = 'remote';
    case HYBRID = 'hybrid';
}
