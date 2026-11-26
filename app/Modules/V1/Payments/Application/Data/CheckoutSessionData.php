<?php

namespace App\Modules\V1\Payments\Application\Data;

use App\Modules\V1\Payments\Domain\ValueObjects\CheckoutModeEnum;
use DateTimeInterface;

final readonly class CheckoutSessionData
{
    /** @param array<string, mixed> $publicConfig @param array<string, mixed> $providerData */
    public function __construct(
        public CheckoutModeEnum $mode,
        public ?string $providerReference = null,
        public ?string $checkoutUrl = null,
        public ?string $clientToken = null,
        public array $publicConfig = [],
        public array $providerData = [],
        public ?DateTimeInterface $expiresAt = null,
    ) {}
}
