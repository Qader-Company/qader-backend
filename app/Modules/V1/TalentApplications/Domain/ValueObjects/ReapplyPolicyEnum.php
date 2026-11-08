<?php

namespace App\Modules\V1\TalentApplications\Domain\ValueObjects;

enum ReapplyPolicyEnum: string
{
    case ALLOWED_AFTER_PERIOD = 'allowed_after_period';
    case BLACKLISTED = 'blacklisted';
}
