<?php

namespace App\Modules\V1\JobForms\Domain\ValueObjects;

enum JobWorkTypeEnum: string
{
    case FULL_TIME = 'full_time';
    case PART_TIME = 'part_time';
    case CONTRACT = 'contract';
    case FREELANCE = 'freelance';
    case INTERNSHIP = 'internship';
}
