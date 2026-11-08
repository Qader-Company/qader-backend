<?php

namespace App\Modules\V1\HiringRequests\Domain\ValueObjects;

enum HiringContractTypeEnum: string
{
    case MANAGED = 'managed';
    case ONE_TIME = 'one_time';
}
