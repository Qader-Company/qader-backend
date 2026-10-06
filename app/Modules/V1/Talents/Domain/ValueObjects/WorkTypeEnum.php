<?php

namespace App\Modules\V1\Talents\Domain\ValueObjects;

enum WorkTypeEnum: string
{
    case FULL_TIME = 'full_time';
    case PART_TIME = 'part_time';
}
