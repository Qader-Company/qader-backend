<?php

namespace App\Modules\V1\HiringRequests\Domain\ValueObjects;

enum HiringOfferSortEnum: string
{
    case NEWEST = 'newest';
    case OLDEST = 'oldest';
}
