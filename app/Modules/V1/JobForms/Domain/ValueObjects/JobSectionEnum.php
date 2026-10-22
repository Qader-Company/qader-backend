<?php

namespace App\Modules\V1\JobForms\Domain\ValueObjects;

enum JobSectionEnum: string
{
    case RESPONSIBILITY = 'responsibility';
    case REQUIREMENT = 'requirement';
    case QUALIFICATION = 'qualification';
}
