<?php

namespace Tests\Feature;

use App\Modules\V1\AccessControl\Domain\Models\Role;
use App\Modules\V1\Admins\Domain\Models\Admin;
use App\Modules\V1\TalentApplications\Application\Services\TalentApplicationReviewWorkflow;
use App\Modules\V1\TalentApplications\Domain\Exceptions\InterviewScheduleConflictException;
use App\Modules\V1\TalentApplications\Domain\Models\TalentApplication;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\TalentApplicationStatusEnum;
use App\Modules\V1\Talents\Domain\Models\Talent;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Database\Seeders\AdminRoleSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TalentApplicationReviewWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private TalentApplicationReviewWorkflow $workflow;

    private Admin $reviewer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, AdminRoleSeeder::class]);
        $this->workflow = app(TalentApplicationReviewWorkflow::class);
        $this->reviewer = $this->createReviewer('reviewer@example.test');
    }

    public function test_start_review_assigns_the_current_reviewer_atomically(): void
    {
        $application = $this->createTalentApplication();

        $result = $this->workflow->startReview($application->id, $this->reviewer);

        $this->assertSame($this->reviewer->id, $result->assigned_admin_id);
        $this->assertSame(TalentApplicationStatusEnum::UNDER_REVIEW, $result->status);
        $this->assertDatabaseHas('talent_application_reviewer_assignments', [
            'application_id' => $application->id,
            'reviewer_admin_id' => $this->reviewer->id,
            'assigned_by_admin_id' => $this->reviewer->id,
            'ended_at' => null,
        ]);
    }

    public function test_reviewer_foreign_keys_are_cast_to_integers(): void
    {
        $application = $this->createTalentApplication();
        $application->setRawAttributes([
            ...$application->getAttributes(),
            'assigned_admin_id' => (string) $this->reviewer->id,
            'review_decided_by_admin_id' => (string) $this->reviewer->id,
        ]);

        $this->assertSame($this->reviewer->id, $application->assigned_admin_id);
        $this->assertSame($this->reviewer->id, $application->review_decided_by_admin_id);
    }

    public function test_complete_evaluation_can_be_rejected_with_calculated_reapply_date(): void
    {
        $application = $this->createTalentApplication();
        $this->workflow->startReview($application->id, $this->reviewer);
        $this->saveCompleteEvaluation($application);

        $rejected = $this->workflow->reject($application->id, [
            'reapply_policy' => 'allowed_after_period',
            'reapply_after_months' => 6,
        ], $this->reviewer);

        $this->assertSame(TalentApplicationStatusEnum::REJECTED, $rejected->status);
        $this->assertNotNull($rejected->review_decided_at);
        $this->assertTrue(
            $rejected->review_decided_at->copy()->addMonthsNoOverflow(6)->equalTo($rejected->reapply_at)
        );
    }

    public function test_interviewer_cannot_be_booked_in_overlapping_slots(): void
    {
        $first = $this->createTalentApplication();
        $this->prepareForDecision($first);
        $start = now()->addDay()->startOfHour();

        $this->workflow->scheduleInterview($first->id, [
            'type' => 'technical',
            'interviewer_admin_ids' => [$this->reviewer->id],
            'slots' => [['mode' => 'online', 'start' => $start->toISOString()]],
        ], $this->reviewer);

        $second = $this->createTalentApplication();
        $this->prepareForDecision($second);

        $this->expectException(InterviewScheduleConflictException::class);
        $this->workflow->scheduleInterview($second->id, [
            'type' => 'technical',
            'interviewer_admin_ids' => [$this->reviewer->id],
            'slots' => [['mode' => 'online', 'start' => $start->copy()->addMinutes(30)->toISOString()]],
        ], $this->reviewer);
    }

    private function createReviewer(string $email): Admin
    {
        $user = User::query()->create([
            'first_name' => 'Review',
            'last_name' => 'Admin',
            'email' => $email,
            'password' => 'Password123!',
            'type' => PortalTypeEnum::ADMIN,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $user->syncRoles([
            Role::query()->where('name', 'reviewer')->where('portal', PortalTypeEnum::ADMIN->value)->firstOrFail(),
        ]);

        return Admin::query()->create(['user_id' => $user->id]);
    }

    private function createTalentApplication(): TalentApplication
    {
        $sequence = TalentApplication::query()->count() + 1;
        $user = User::query()->create([
            'first_name' => 'Talent',
            'last_name' => (string) $sequence,
            'email' => "talent{$sequence}@example.test",
            'password' => 'Password123!',
            'type' => PortalTypeEnum::TALENT,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $talent = Talent::query()->create(['user_id' => $user->id]);

        return TalentApplication::query()->create([
            'talent_id' => $talent->id,
            'status' => TalentApplicationStatusEnum::SUBMITTED,
            'submitted_at' => now(),
            'submission_snapshot' => ['user' => ['first_name' => 'Talent', 'last_name' => (string) $sequence, 'email' => $user->email]],
            'profile_completion_at_submission' => 100,
            'snapshot_version' => 1,
            'snapshot_captured_at' => now(),
        ]);
    }

    private function prepareForDecision(TalentApplication $application): void
    {
        $this->workflow->startReview($application->id, $this->reviewer);
        $this->saveCompleteEvaluation($application);
    }

    private function saveCompleteEvaluation(TalentApplication $application): void
    {
        $this->workflow->saveEvaluation($application->id, [
            'technical_skills_rating' => 4,
            'communication_skills_rating' => 4,
            'problem_solving_rating' => 4,
            'soft_skills_rating' => 4,
            'tips' => [],
        ], $this->reviewer);
    }
}
