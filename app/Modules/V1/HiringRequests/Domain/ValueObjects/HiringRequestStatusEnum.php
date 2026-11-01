<?php

namespace App\Modules\V1\HiringRequests\Domain\ValueObjects;

enum HiringRequestStatusEnum: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
