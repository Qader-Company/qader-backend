<?php

namespace App\Modules\V1\Payments\Domain\ValueObjects;

enum PaymentWebhookStatusEnum: string
{
    case RECEIVED = 'received';
    case PROCESSED = 'processed';
    case FAILED = 'failed';
}
