<?php

namespace App\Modules\V1\Payments\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentCheckoutResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $session = $this->resource['session'];

        return [
            'payment' => new PaymentResource($this->resource['payment']),
            'checkout' => $session ? [
                'mode' => $session->mode->value,
                'checkout_url' => $session->checkoutUrl,
                'client_token' => $session->clientToken,
                'public_config' => $session->publicConfig,
                'expires_at' => $session->expiresAt?->format(DATE_ATOM),
            ] : null,
        ];
    }
}
