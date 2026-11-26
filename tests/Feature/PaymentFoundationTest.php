<?php

namespace Tests\Feature;

use App\Modules\V1\Clients\Domain\Models\Client;
use App\Modules\V1\Countries\Domain\Models\Country;
use App\Modules\V1\Payments\Application\Services\CreatePaymentCheckoutService;
use App\Modules\V1\Payments\Application\Services\PaidServiceAccessService;
use App\Modules\V1\Payments\Application\Services\ProcessPaymentWebhookService;
use App\Modules\V1\Payments\Domain\Models\Payment;
use App\Modules\V1\Payments\Domain\Models\PaymentAttempt;
use App\Modules\V1\Payments\Domain\Models\PaymentWebhookEvent;
use App\Modules\V1\Payments\Domain\ValueObjects\PaidServiceEnum;
use App\Modules\V1\Payments\Domain\ValueObjects\PaymentAttemptStatusEnum;
use App\Modules\V1\Payments\Domain\ValueObjects\PaymentStatusEnum;
use App\Modules\V1\TalentApplications\Domain\Models\TalentApplication;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\TalentApplicationStatusEnum;
use App\Modules\V1\Talents\Domain\Models\Talent;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Modules\V1\Workspaces\Domain\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_backend_reuses_the_same_payment_and_active_attempt_for_the_same_purchase(): void
    {
        [$user, $application] = $this->acceptedTalentApplication();
        $service = app(CreatePaymentCheckoutService::class);

        $first = $service->forTalent($user, $application);
        $second = $service->forTalent($user, $application);

        $this->assertSame($first['payment']->id, $second['payment']->id);
        $this->assertSame($first['attempt']->id, $second['attempt']->id);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('payment_attempts', 1);
    }

    public function test_backend_creates_a_new_attempt_after_the_previous_attempt_fails(): void
    {
        [$user, $application] = $this->acceptedTalentApplication();
        $service = app(CreatePaymentCheckoutService::class);
        $first = $service->forTalent($user, $application);

        $first['attempt']->update([
            'attempt_status' => PaymentAttemptStatusEnum::FAILED,
            'completed_at' => now(),
        ]);
        $first['payment']->update(['status' => PaymentStatusEnum::FAILED]);

        $second = $service->forTalent($user, $application);

        $this->assertSame($first['payment']->id, $second['payment']->id);
        $this->assertNotSame($first['attempt']->id, $second['attempt']->id);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('payment_attempts', 2);
    }

    public function test_signed_webhook_marks_payment_paid_and_duplicate_is_ignored(): void
    {
        [$user, $application] = $this->acceptedTalentApplication();
        $payment = Payment::query()->create([
            'reference' => (string) Str::uuid(),
            'purchase_key' => hash('sha256', 'webhook-test'),
            'payer_user_id' => $user->id,
            'talent_id' => $application->talent_id,
            'talent_application_id' => $application->id,
            'service' => PaidServiceEnum::PROFILE_EXPERT_REVIEW,
            'amount' => '10.0000',
            'currency' => 'USD',
            'status' => PaymentStatusEnum::PROCESSING,
        ]);
        $attempt = PaymentAttempt::query()->create([
            'payment_id' => $payment->id,
            'gateway_request_id' => (string) Str::uuid(),
            'provider' => 'fake',
            'provider_reference' => 'fake-reference-1',
            'attempt_status' => PaymentAttemptStatusEnum::PROCESSING,
        ]);
        $payload = json_encode([
            'event_id' => 'evt-1',
            'event_type' => 'payment.paid',
            'gateway_request_id' => $attempt->gateway_request_id,
            'provider_reference' => $attempt->provider_reference,
            'status' => 'paid',
            'amount_minor' => 1000,
            'currency' => 'USD',
        ], JSON_THROW_ON_ERROR);
        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_X_PAYMENT_SIGNATURE' => hash_hmac('sha256', $payload, 'test-payment-secret'),
            'CONTENT_TYPE' => 'application/json',
        ], $payload);
        $processor = app(ProcessPaymentWebhookService::class);

        $processor->handle('fake', $request);
        $processor->handle('fake', $request);

        $this->assertSame(PaymentStatusEnum::PAID, $payment->fresh()->status);
        $this->assertNotNull($payment->fresh()->paid_at);
        $this->assertDatabaseCount('payment_webhook_events', 1);
        $this->assertSame('processed', PaymentWebhookEvent::query()->firstOrFail()->processing_status->value);
    }

    public function test_client_purchase_is_shared_by_all_workspaces_of_the_same_owner(): void
    {
        [$talentUser] = $this->acceptedTalentApplication();
        $talent = $talentUser->talent()->firstOrFail();
        $ownerUser = User::query()->create([
            'first_name' => 'Owner',
            'last_name' => 'Client',
            'email' => Str::uuid().'@example.test',
            'password' => 'Password123!',
            'type' => PortalTypeEnum::CLIENT,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $owner = Client::query()->create(['user_id' => $ownerUser->id]);
        $country = Country::query()->create([
            'code' => 'EG',
            'is_active' => true,
            'en' => ['name' => 'Egypt'],
            'ar' => ['name' => 'مصر'],
        ]);
        $firstWorkspace = Workspace::query()->create([
            'owner_client_id' => $owner->id,
            'name' => 'First',
            'description' => 'First workspace',
            'is_company' => false,
            'country_id' => $country->id,
            'size_band' => '1-10',
        ]);
        $secondWorkspace = Workspace::query()->create([
            'owner_client_id' => $owner->id,
            'name' => 'Second',
            'description' => 'Second workspace',
            'is_company' => false,
            'country_id' => $country->id,
            'size_band' => '1-10',
        ]);

        Payment::query()->create([
            'reference' => (string) Str::uuid(),
            'purchase_key' => hash('sha256', 'shared-owner-purchase'),
            'payer_user_id' => $ownerUser->id,
            'owner_client_id' => $owner->id,
            'talent_id' => $talent->id,
            'service' => PaidServiceEnum::QADER_EXPERT_OPINION,
            'amount' => '250.0000',
            'currency' => 'EGP',
            'status' => PaymentStatusEnum::PAID,
            'paid_at' => now(),
        ]);

        $access = app(PaidServiceAccessService::class);

        $this->assertTrue($access->clientHasExpertOpinion($firstWorkspace, $talent));
        $this->assertTrue($access->clientHasExpertOpinion($secondWorkspace, $talent));
    }

    /** @return array{User, TalentApplication} */
    private function acceptedTalentApplication(): array
    {
        $user = User::query()->create([
            'first_name' => 'Paid',
            'last_name' => 'Talent',
            'email' => Str::uuid().'@example.test',
            'password' => 'Password123!',
            'type' => PortalTypeEnum::TALENT,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $talent = Talent::query()->create(['user_id' => $user->id]);
        $application = TalentApplication::query()->create([
            'talent_id' => $talent->id,
            'status' => TalentApplicationStatusEnum::ACCEPTED,
            'submitted_at' => now(),
            'submission_snapshot' => [],
            'profile_completion_at_submission' => 100,
            'snapshot_version' => 1,
            'snapshot_captured_at' => now(),
        ]);

        return [$user, $application];
    }
}
