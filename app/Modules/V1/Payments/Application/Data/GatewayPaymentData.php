<?php

namespace App\Modules\V1\Payments\Application\Data;

use App\Modules\V1\Payments\Domain\ValueObjects\PaymentAttemptStatusEnum;

final readonly class GatewayPaymentData
{
    /** @param array<string, mixed> $providerData */
    public function __construct(
        public PaymentAttemptStatusEnum $status,
        public int $amountMinor,
        public string $currency,
        public ?string $providerReference = null,
        public ?string $gatewayRequestId = null,
        public array $providerData = [],
    ) {}
}
