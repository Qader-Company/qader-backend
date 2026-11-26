<?php

namespace App\Modules\V1\Payments\Domain\ValueObjects;

enum CheckoutModeEnum: string
{
    case REDIRECT = 'redirect';
    case EMBEDDED = 'embedded';
    case SDK = 'sdk';
}
