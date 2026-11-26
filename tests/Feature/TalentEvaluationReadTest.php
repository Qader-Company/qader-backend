<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckApiKey;
use App\Modules\V1\Admins\Domain\Models\Admin;
use App\Modules\V1\Payments\Domain\Models\Payment;
use App\Modules\V1\Payments\Domain\ValueObjects\PaidServiceEnum;
use App\Modules\V1\Payments\Domain\ValueObjects\PaymentStatusEnum;
use App\Modules\V1\TalentApplications\Domain\Models\TalentApplication;
use App\Modules\V1\TalentApplications\Domain\Models\TalentEvaluation;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\TalentApplicationStatusEnum;
use App\Modules\V1\Talents\Domain\Models\Talent;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TalentEvaluationReadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(CheckApiKey::class);
    }

    public function test_profile_exposes_evaluation_only_after_payment_without_embedding_the_evaluation(): void
    {
        [$user, $talent, $application] = $this->createEvaluatedTalent();
        Sanctum::actingAs($user, ['access', 'talent']);

        $this->getJson('/api/v1/talent/profile')
            ->assertOk()
            ->assertJsonPath('data.evaluation_is_available', false);

        $this->recordPaidEvaluation($user, $talent, $application);

        $this->getJson('/api/v1/talent/profile')
            ->assertOk()
            ->assertJsonPath('data.evaluation_is_available', true)
            ->assertJsonMissingPath('data.evaluation');

        $this->getJson('/api/v1/talent/application')
            ->assertOk()
            ->assertJsonPath('data.id', $application->id)
            ->assertJsonPath('data.evaluation_is_available', true)
            ->assertJsonMissingPath('data.evaluation');
    }

    public function test_talent_can_read_the_dedicated_evaluation_after_payment(): void
    {
        [$user, $talent, $application] = $this->createEvaluatedTalent();
        Sanctum::actingAs($user, ['access', 'talent']);
        $this->recordPaidEvaluation($user, $talent, $application);

        $this->getJson('/api/v1/talent/profile/evaluation')
            ->assertOk()
            ->assertJsonPath('data.application_id', $application->id)
            ->assertJsonPath('data.profile_review.ratings.technical_skills', 4)
            ->assertJsonPath('data.profile_review.average_rating', 4.25)
            ->assertJsonPath('data.interview_reviews', []);
    }

    public function test_evaluation_endpoint_returns_not_found_until_an_evaluation_exists(): void
    {
        $user = $this->createTalentUser('without-evaluation@example.test');
        Talent::query()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user, ['access', 'talent']);

        $this->getJson('/api/v1/talent/profile/evaluation')
            ->assertNotFound()
            ->assertJsonPath('message', __('talents.evaluation.not_available'));
    }

    /** @return array{User, Talent, TalentApplication} */
    private function createEvaluatedTalent(): array
    {
        $user = $this->createTalentUser('evaluated-talent@example.test');
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
        $adminUser = User::query()->create([
            'first_name' => 'Review',
            'last_name' => 'Admin',
            'email' => 'evaluation-admin@example.test',
            'password' => 'Password123!',
            'type' => PortalTypeEnum::ADMIN,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $admin = Admin::query()->create(['user_id' => $adminUser->id]);

        TalentEvaluation::query()->create([
            'application_id' => $application->id,
            'evaluator_admin_id' => $admin->id,
            'technical_skills_rating' => 4,
            'communication_skills_rating' => 4,
            'problem_solving_rating' => 5,
            'soft_skills_rating' => 4,
            'hr_report' => 'Clear communicator.',
            'technical_report' => 'Strong technical fundamentals.',
            'psychology_report' => 'Calm under pressure.',
        ]);

        return [$user, $talent, $application];
    }

    private function createTalentUser(string $email): User
    {
        return User::query()->create([
            'first_name' => 'Talent',
            'last_name' => 'Person',
            'email' => $email,
            'password' => 'Password123!',
            'type' => PortalTypeEnum::TALENT,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }

    private function recordPaidEvaluation(User $user, Talent $talent, TalentApplication $application): void
    {
        Payment::query()->create([
            'reference' => (string) Str::uuid(),
            'purchase_key' => hash('sha256', 'evaluation-'.$application->id),
            'payer_user_id' => $user->id,
            'talent_id' => $talent->id,
            'talent_application_id' => $application->id,
            'service' => PaidServiceEnum::PROFILE_EXPERT_REVIEW,
            'amount' => '10.0000',
            'currency' => 'USD',
            'status' => PaymentStatusEnum::PAID,
            'paid_at' => now(),
        ]);
    }
}
