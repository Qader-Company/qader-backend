<?php

namespace App\Modules\V1\Talents\Domain\ValueObjects;

enum SeniorityLevelEnum: string
{
    case INTERN = 'intern';
    case JUNIOR = 'junior';
    case MID_LEVEL = 'mid_level';
    case SENIOR = 'senior';
    case LEAD = 'lead';
}
