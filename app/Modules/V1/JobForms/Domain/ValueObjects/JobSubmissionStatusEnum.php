<?php

namespace App\Modules\V1\JobForms\Domain\ValueObjects;

enum JobSubmissionStatusEnum: string
{
    case RECEIVED = 'received';
    case HIRING_STARTED = 'hiring_started';
}
