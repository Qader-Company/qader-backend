<?php

namespace App\Modules\V1\TalentApplications\Domain\ValueObjects;

enum TalentApplicationListScopeEnum: string
{
    case ALL = 'all';
    case MY = 'my';
}
