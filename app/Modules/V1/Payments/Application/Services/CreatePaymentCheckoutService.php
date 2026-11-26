<?php

namespace App\Modules\V1\Payments\Application\Services;

use App\Modules\V1\Payments\Application\Data\CheckoutSessionData;
use App\Modules\V1\Payments\Domain\Models\Payment;
use App\Modules\V1\Payments\Domain\Models\PaymentAttempt;
use App\Modules\V1\Payments\Domain\ValueObjects\PaidServiceEnum;
use App\Modules\V1\Payments\Domain\ValueObjects\PaymentAttemptStatusEnum;
use App\Modules\V1\Payments\Domain\ValueObjects\PaymentStatusEnum;
use App\Modules\V1\Payments\Infrastructure\Gateways\PaymentGatewayManager;
use App\Modules\V1\TalentApplications\Domain\Models\TalentApplication;
use App\Modules\V1\Talents\Domain\Models\Talent;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Workspaces\Domain\Models\Workspace;
use App\Settings\PaidServicesSettings;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CreatePaymentCheckoutService
{
    public function __construct(
        private readonly PaymentGatewayManager $gateways,
        private readonly PaidServicesSettings $settings,
        private readonly PaidServiceAccessService $access,
    ) {}

    /** @return array{payment: Payment, attempt: ?PaymentAttempt, session: ?CheckoutSessionData} */
    public function forClient(User $user, Workspace $workspace, Talent $talent, string $idempotencyKey): array
    {
        return $this->create($user, PaidServiceEnum::QADER_EXPERT_OPINION, $idempotencyKey, [
            'owner_client_id' => $workspace->owner_client_id,
            'talent_id' => $talent->id,
        ]);
    }

    /** @return array{payment: Payment, attempt: ?PaymentAttempt, session: ?CheckoutSessionData} */
    public function forTalent(User $user, TalentApplication $application, string $idempotencyKey): array
    {
        $service = $this->access->evaluationService($application)
            ?? throw new NotFoundHttpException(__('payments.evaluation_not_purchasable'));

        return $this->create($user, $service, $idempotencyKey, [
            'talent_id' => $application->talent_id,
            'talent_application_id' => $application->id,
        ]);
    }

    /** @param array<string, int|null> $target @return array{payment: Payment, attempt: ?PaymentAttempt, session: ?CheckoutSessionData} */
    private function create(User $user, PaidServiceEnum $service, string $idempotencyKey, array $target): array
    {
        $gateway = $this->gateways->default();
        $price = $service->price($this->settings);
        $fingerprint = hash('sha256', json_encode([$user->id, $service->value, $target, $price], JSON_THROW_ON_ERROR));

        $payment = Payment::query()
            ->where('payer_user_id', $user->id)
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($payment && ! hash_equals($payment->request_fingerprint, $fingerprint)) {
            throw new ConflictHttpException(__('payments.idempotency_conflict'));
        }

        $payment ??= Payment::query()
            ->where($target)
            ->where('service', $service->value)
            ->where('status', PaymentStatusEnum::PAID->value)
            ->latest('id')
            ->first();

        if ($payment?->status === PaymentStatusEnum::PAID) {
            return ['payment' => $payment, 'attempt' => null, 'session' => null];
        }

        try {
            $attempt = DB::transaction(function () use (&$payment, $user, $service, $idempotencyKey, $fingerprint, $price, $target, $gateway): PaymentAttempt {
                $payment ??= Payment::query()->create([
                    'reference' => (string) Str::uuid(),
                    'idempotency_key' => $idempotencyKey,
                    'request_fingerprint' => $fingerprint,
                    'payer_user_id' => $user->id,
                    ...$target,
                    'service' => $service,
                    'amount' => $price['amount'],
                    'currency' => $price['currency'],
                    'status' => PaymentStatusEnum::PENDING,
                ]);

                return $payment->attempts()->latest('id')->firstOrCreate([], [
                    'gateway_request_id' => (string) Str::uuid(),
                    'provider' => $gateway->name(),
                    'attempt_status' => PaymentAttemptStatusEnum::PENDING,
                ]);
            });
        } catch (UniqueConstraintViolationException) {
            $payment = Payment::query()
                ->where('payer_user_id', $user->id)
                ->where('idempotency_key', $idempotencyKey)
                ->firstOrFail();

            if (! hash_equals($payment->request_fingerprint, $fingerprint)) {
                throw new ConflictHttpException(__('payments.idempotency_conflict'));
            }

            $attempt = $payment->attempts()->latest('id')->firstOrFail();
        }

        if ($attempt->checkout_mode !== null) {
            return ['payment' => $payment->fresh(), 'attempt' => $attempt, 'session' => $this->sessionFrom($attempt)];
        }

        $session = $gateway->createCheckout($attempt->load('payment'));
        $attempt->forceFill([
            'provider_reference' => $session->providerReference,
            'attempt_status' => PaymentAttemptStatusEnum::PROCESSING,
            'checkout_mode' => $session->mode,
            'checkout_url' => $session->checkoutUrl,
            'client_token' => $session->clientToken,
            'provider_data' => $session->providerData,
            'expires_at' => $session->expiresAt,
        ])->save();
        $payment->forceFill(['status' => PaymentStatusEnum::PROCESSING])->save();

        return ['payment' => $payment->fresh(), 'attempt' => $attempt->fresh(), 'session' => $session];
    }

    private function sessionFrom(PaymentAttempt $attempt): CheckoutSessionData
    {
        return new CheckoutSessionData(
            mode: $attempt->checkout_mode,
            providerReference: $attempt->provider_reference,
            checkoutUrl: $attempt->checkout_url,
            clientToken: $attempt->client_token,
            publicConfig: ['provider' => $attempt->provider],
            providerData: $attempt->provider_data ?? [],
            expiresAt: $attempt->expires_at,
        );
    }
}
