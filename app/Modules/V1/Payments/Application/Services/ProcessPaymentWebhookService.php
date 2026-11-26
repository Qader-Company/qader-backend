<?php

namespace App\Modules\V1\Payments\Application\Services;

use App\Modules\V1\Payments\Application\Data\PaymentWebhookData;
use App\Modules\V1\Payments\Application\Jobs\ProcessPaymentWebhookJob;
use App\Modules\V1\Payments\Domain\Models\PaymentAttempt;
use App\Modules\V1\Payments\Domain\Models\PaymentWebhookEvent;
use App\Modules\V1\Payments\Domain\ValueObjects\PaymentAttemptStatusEnum;
use App\Modules\V1\Payments\Domain\ValueObjects\PaymentStatusEnum;
use App\Modules\V1\Payments\Domain\ValueObjects\PaymentWebhookStatusEnum;
use App\Modules\V1\Payments\Infrastructure\Gateways\PaymentGatewayManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use UnexpectedValueException;

class ProcessPaymentWebhookService
{
    public function __construct(
        private readonly PaymentGatewayManager $gateways,
        private readonly MinorAmountConverter $amounts,
    ) {}

    public function handle(string $provider, Request $request): PaymentWebhookEvent
    {
        $gateway = $this->gateways->for($provider);
        $data = $gateway->parseWebhook($request);
        $attempt = $this->attempt($provider, $data);

        $event = PaymentWebhookEvent::query()->firstOrCreate([
            'payment_attempt_id' => $attempt->id,
            'event_id' => $data->eventId,
        ], [
            'event_type' => $data->eventType,
            'payload' => $data->payload,
            'processing_status' => PaymentWebhookStatusEnum::RECEIVED,
        ]);

        if ($event->wasRecentlyCreated || $event->processing_status === PaymentWebhookStatusEnum::FAILED) {
            ProcessPaymentWebhookJob::dispatch($event->id, $data);
        }

        return $event->fresh();
    }

    public function process(PaymentWebhookEvent $event, PaymentWebhookData $data): void
    {
        try {
            DB::transaction(function () use ($event, $data): void {
                $attempt = PaymentAttempt::query()->with('payment')->lockForUpdate()->findOrFail($event->payment_attempt_id);
                $payment = $attempt->payment;

                if ($this->amounts->toMinor($payment->amount, $payment->currency) !== $data->amountMinor
                    || strtoupper($payment->currency) !== strtoupper($data->currency)) {
                    throw new UnexpectedValueException(__('payments.webhook_amount_mismatch'));
                }

                $canTransition = ! $attempt->attempt_status->isFinal()
                    || $data->status === PaymentAttemptStatusEnum::PAID;

                if ($attempt->attempt_status !== PaymentAttemptStatusEnum::PAID && $canTransition) {
                    $attempt->forceFill([
                        'provider_reference' => $data->providerReference ?? $attempt->provider_reference,
                        'attempt_status' => $data->status,
                        'provider_data' => array_merge($attempt->provider_data ?? [], $data->providerData),
                        'completed_at' => $data->status->isFinal() ? now() : null,
                    ])->save();

                    $payment->forceFill([
                        'status' => PaymentStatusEnum::from($data->status->value),
                        'paid_at' => $data->status === PaymentAttemptStatusEnum::PAID ? ($payment->paid_at ?? now()) : null,
                    ])->save();
                }

                $event->forceFill([
                    'processing_status' => PaymentWebhookStatusEnum::PROCESSED,
                    'processed_at' => now(),
                    'processing_error' => null,
                ])->save();
            });
        } catch (\Throwable $exception) {
            $event->forceFill([
                'processing_status' => PaymentWebhookStatusEnum::FAILED,
                'processing_error' => $exception->getMessage(),
            ])->save();
            throw $exception;
        }
    }

    private function attempt(string $provider, PaymentWebhookData $data): PaymentAttempt
    {
        $attempt = PaymentAttempt::query()
            ->where('provider', $provider)
            ->when($data->providerReference, fn ($query) => $query->where('provider_reference', $data->providerReference))
            ->first();

        $attempt ??= PaymentAttempt::query()
            ->where('provider', $provider)
            ->where('gateway_request_id', $data->gatewayRequestId)
            ->first();

        return $attempt ?? throw new NotFoundHttpException(__('payments.payment_attempt_not_found'));
    }
}
