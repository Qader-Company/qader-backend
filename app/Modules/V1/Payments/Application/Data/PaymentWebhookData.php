<?php

namespace App\Modules\V1\Payments\Application\Data;

use App\Modules\V1\Payments\Domain\ValueObjects\PaymentAttemptStatusEnum;

final readonly class PaymentWebhookData
{
    /** @param array<string, mixed> $payload @param array<string, mixed> $providerData */
    public function __construct(
        public string $eventId,
        public string $eventType,
        public PaymentAttemptStatusEnum $status,
        public int $amountMinor,
        public string $currency,
        public array $payload,
        public ?string $providerReference = null,
        public ?string $gatewayRequestId = null,
        public array $providerData = [],
    ) {}
}
