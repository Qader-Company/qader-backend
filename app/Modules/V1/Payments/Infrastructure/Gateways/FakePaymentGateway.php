<?php

namespace App\Modules\V1\Payments\Infrastructure\Gateways;

use App\Modules\V1\Payments\Application\Contracts\PaymentGatewayInterface;
use App\Modules\V1\Payments\Application\Data\CheckoutSessionData;
use App\Modules\V1\Payments\Application\Data\GatewayPaymentData;
use App\Modules\V1\Payments\Application\Data\PaymentWebhookData;
use App\Modules\V1\Payments\Application\Services\MinorAmountConverter;
use App\Modules\V1\Payments\Domain\Models\PaymentAttempt;
use App\Modules\V1\Payments\Domain\ValueObjects\CheckoutModeEnum;
use App\Modules\V1\Payments\Domain\ValueObjects\PaymentAttemptStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class FakePaymentGateway implements PaymentGatewayInterface
{
    public function __construct(private readonly MinorAmountConverter $amounts) {}

    public function name(): string
    {
        return 'fake';
    }

    public function createCheckout(PaymentAttempt $attempt): CheckoutSessionData
    {
        return new CheckoutSessionData(
            mode: CheckoutModeEnum::SDK,
            providerReference: $attempt->gateway_request_id,
            publicConfig: ['provider' => 'fake'],
            providerData: ['gateway_request_id' => $attempt->gateway_request_id],
            expiresAt: now()->addMinutes(30),
        );
    }

    public function parseWebhook(Request $request): PaymentWebhookData
    {
        $raw = $request->getContent();
        $expected = hash_hmac('sha256', $raw, (string) config('payments.gateways.fake.webhook_secret'));
        $provided = (string) $request->header('X-Payment-Signature');

        if ($provided === '' || ! hash_equals($expected, $provided)) {
            throw new AccessDeniedHttpException(__('payments.invalid_webhook_signature'));
        }

        $payload = json_decode($raw, true);
        if (! is_array($payload)) {
            throw ValidationException::withMessages(['payload' => [__('payments.invalid_webhook_payload')]]);
        }

        $validator = validator($payload, [
            'event_id' => ['required', 'string', 'max:255'],
            'event_type' => ['required', 'string', 'max:100'],
            'gateway_request_id' => ['required', 'uuid'],
            'provider_reference' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:pending,processing,paid,failed,cancelled,expired'],
            'amount_minor' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'provider_data' => ['nullable', 'array'],
        ]);

        $data = $validator->validate();

        return new PaymentWebhookData(
            eventId: $data['event_id'],
            eventType: $data['event_type'],
            status: PaymentAttemptStatusEnum::from($data['status']),
            amountMinor: (int) $data['amount_minor'],
            currency: strtoupper($data['currency']),
            payload: Arr::except($payload, ['signature', 'secret']),
            providerReference: $data['provider_reference'] ?? null,
            gatewayRequestId: $data['gateway_request_id'],
            providerData: $data['provider_data'] ?? [],
        );
    }

    public function fetchPayment(PaymentAttempt $attempt): GatewayPaymentData
    {
        $attempt->loadMissing('payment');
        $status = $attempt->attempt_status === PaymentAttemptStatusEnum::PENDING
            ? PaymentAttemptStatusEnum::PROCESSING
            : $attempt->attempt_status;

        return new GatewayPaymentData(
            status: $status,
            amountMinor: $this->amounts->toMinor($attempt->payment->amount, $attempt->payment->currency),
            currency: $attempt->payment->currency,
            providerReference: $attempt->provider_reference ?? $attempt->gateway_request_id,
            gatewayRequestId: $attempt->gateway_request_id,
            providerData: array_merge($attempt->provider_data ?? [], ['reconciled_at' => now()->toISOString()]),
        );
    }
}
