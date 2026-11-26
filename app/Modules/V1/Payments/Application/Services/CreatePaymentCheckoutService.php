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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CreatePaymentCheckoutService
{
    public function __construct(
        private readonly PaymentGatewayManager $gateways,
        private readonly PaidServicesSettings $settings,
        private readonly PaidServiceAccessService $access,
    ) {}

    /** @return array{payment: Payment, attempt: ?PaymentAttempt, session: ?CheckoutSessionData} */
    public function forClient(User $user, Workspace $workspace, Talent $talent): array
    {
        return $this->create($user, PaidServiceEnum::QADER_EXPERT_OPINION, [
            'owner_client_id' => $workspace->owner_client_id,
            'talent_id' => $talent->id,
        ]);
    }

    /** @return array{payment: Payment, attempt: ?PaymentAttempt, session: ?CheckoutSessionData} */
    public function forTalent(User $user, TalentApplication $application): array
    {
        $service = $this->access->evaluationService($application)
            ?? throw new NotFoundHttpException(__('payments.evaluation_not_purchasable'));

        return $this->create($user, $service, [
            'talent_id' => $application->talent_id,
            'talent_application_id' => $application->id,
        ]);
    }

    /** @param array<string, int|null> $target @return array{payment: Payment, attempt: ?PaymentAttempt, session: ?CheckoutSessionData} */
    private function create(User $user, PaidServiceEnum $service, array $target): array
    {
        $gateway = $this->gateways->default();
        $price = $service->price($this->settings);
        $purchaseKey = $this->purchaseKey($service, $target);
        $payment = Payment::query()->firstOrCreate([
            'purchase_key' => $purchaseKey,
        ], [
            'reference' => (string) Str::uuid(),
            'payer_user_id' => $user->id,
            ...$target,
            'service' => $service,
            'amount' => $price['amount'],
            'currency' => $price['currency'],
            'status' => PaymentStatusEnum::PENDING,
        ]);

        $checkout = DB::transaction(function () use ($payment, $gateway): array {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($payment->status === PaymentStatusEnum::PAID) {
                return ['payment' => $payment, 'attempt' => null];
            }

            $attempt = $payment->attempts()->latest('id')->first();
            if ($attempt === null || $attempt->attempt_status->isFinal()) {
                $attempt = $payment->attempts()->create([
                    'gateway_request_id' => (string) Str::uuid(),
                    'provider' => $gateway->name(),
                    'attempt_status' => PaymentAttemptStatusEnum::PENDING,
                ]);

                $payment->forceFill([
                    'status' => PaymentStatusEnum::PENDING,
                    'paid_at' => null,
                ])->save();
            }

            return ['payment' => $payment, 'attempt' => $attempt];
        });

        $payment = $checkout['payment'];
        $attempt = $checkout['attempt'];

        if ($attempt === null) {
            return ['payment' => $payment, 'attempt' => null, 'session' => null];
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

    /** @param array<string, int|null> $target */
    private function purchaseKey(PaidServiceEnum $service, array $target): string
    {
        ksort($target);

        return hash('sha256', json_encode([
            'service' => $service->value,
            'target' => $target,
        ], JSON_THROW_ON_ERROR));
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
