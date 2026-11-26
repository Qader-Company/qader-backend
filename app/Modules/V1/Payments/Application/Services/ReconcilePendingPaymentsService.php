<?php

namespace App\Modules\V1\Payments\Application\Services;

use App\Modules\V1\Payments\Application\Data\PaymentWebhookData;
use App\Modules\V1\Payments\Domain\Models\PaymentAttempt;
use App\Modules\V1\Payments\Domain\Models\PaymentWebhookEvent;
use App\Modules\V1\Payments\Domain\ValueObjects\PaymentAttemptStatusEnum;
use App\Modules\V1\Payments\Domain\ValueObjects\PaymentWebhookStatusEnum;
use App\Modules\V1\Payments\Infrastructure\Gateways\PaymentGatewayManager;

class ReconcilePendingPaymentsService
{
    public function __construct(
        private readonly PaymentGatewayManager $gateways,
        private readonly ProcessPaymentWebhookService $processor,
    ) {}

    public function run(int $limit = 100): int
    {
        $attempts = PaymentAttempt::query()
            ->whereIn('attempt_status', [PaymentAttemptStatusEnum::PENDING->value, PaymentAttemptStatusEnum::PROCESSING->value])
            ->where('updated_at', '<=', now()->subMinutes(5))
            ->oldest('updated_at')
            ->limit($limit)
            ->get();

        foreach ($attempts as $attempt) {
            $result = $this->gateways->for($attempt->provider)->fetchPayment($attempt);
            $eventId = 'reconcile:'.$attempt->gateway_request_id.':'.$result->status->value;
            $event = PaymentWebhookEvent::query()->firstOrCreate([
                'payment_attempt_id' => $attempt->id,
                'event_id' => $eventId,
            ], [
                'event_type' => 'payment.reconciled',
                'payload' => $result->providerData,
                'processing_status' => PaymentWebhookStatusEnum::RECEIVED,
            ]);

            if ($event->wasRecentlyCreated) {
                $this->processor->process($event, new PaymentWebhookData(
                    eventId: $eventId,
                    eventType: 'payment.reconciled',
                    status: $result->status,
                    amountMinor: $result->amountMinor,
                    currency: $result->currency,
                    payload: $result->providerData,
                    providerReference: $result->providerReference,
                    gatewayRequestId: $result->gatewayRequestId,
                    providerData: $result->providerData,
                ));
            }
        }

        return $attempts->count();
    }
}
