<?php

namespace App\Modules\V1\TalentApplications\Domain\ValueObjects;

enum TalentApplicationSortEnum: string
{
    case NEWEST = 'newest';
    case OLDEST = 'oldest';
}
