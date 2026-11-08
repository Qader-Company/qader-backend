<?php

namespace App\Modules\V1\HiringRequests\Domain\ValueObjects;

enum HiringRequestListScopeEnum: string
{
    case ALL = 'all';
    case MINE = 'mine';
}
