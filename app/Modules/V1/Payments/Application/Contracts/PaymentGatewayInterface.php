<?php

namespace App\Modules\V1\Payments\Application\Contracts;

use App\Modules\V1\Payments\Application\Data\CheckoutSessionData;
use App\Modules\V1\Payments\Application\Data\GatewayPaymentData;
use App\Modules\V1\Payments\Application\Data\PaymentWebhookData;
use App\Modules\V1\Payments\Domain\Models\PaymentAttempt;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    public function name(): string;

    public function createCheckout(PaymentAttempt $attempt): CheckoutSessionData;

    public function parseWebhook(Request $request): PaymentWebhookData;

    public function fetchPayment(PaymentAttempt $attempt): GatewayPaymentData;
}
