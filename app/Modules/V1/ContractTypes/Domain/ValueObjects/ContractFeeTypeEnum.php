<?php

namespace App\Modules\V1\ContractTypes\Domain\ValueObjects;

enum ContractFeeTypeEnum: string
{
    case FIXED = 'fixed';
    case PERCENTAGE = 'percentage';
    case FIXED_PERCENTAGE = 'fixed_percentage';
}
