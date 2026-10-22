<?php

namespace App\Modules\V1\JobForms\Domain\ValueObjects;

enum JobExperienceRangeEnum: string
{
    case LESS_THAN_ONE = 'less_than_1';
    case ONE_TO_THREE = '1_to_3';
    case THREE_TO_FIVE = '3_to_5';
    case FIVE_TO_TEN = '5_to_10';
    case MORE_THAN_TEN = 'more_than_10';
}
