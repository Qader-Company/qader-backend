<?php

namespace App\Modules\V1\HiringRequests\Domain\ValueObjects;

enum HiringOfferStatusEnum: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
    case COUNTERED = 'countered';
    case EXPIRED = 'expired';
}
