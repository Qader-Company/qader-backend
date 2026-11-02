<?php

namespace App\Modules\V1\HiringRequests\Domain\ValueObjects;

enum HiringOfferPartyEnum: string
{
    case CLIENT = 'client';
    case TALENT = 'talent';
}
