<?php

namespace Tests\Feature;

use App\Modules\V1\AccessControl\Domain\Models\Role;
use App\Modules\V1\Admins\Domain\Models\Admin;
use App\Modules\V1\TalentApplications\Application\Contracts\InterviewCalendarGatewayInterface;
use App\Modules\V1\TalentApplications\Application\Data\CalendarMeetingData;
use App\Modules\V1\TalentApplications\Application\Services\AdminInterviewWorkflow;
use App\Modules\V1\TalentApplications\Application\Services\AdminTalentStateService;
use App\Modules\V1\TalentApplications\Application\Services\InterviewStateSynchronizer;
use App\Modules\V1\TalentApplications\Application\Services\TalentApplicationReviewWorkflow;
use App\Modules\V1\TalentApplications\Application\Services\TalentInterviewWorkflow;
use App\Modules\V1\TalentApplications\Domain\Models\InterviewSlot;
use App\Modules\V1\TalentApplications\Domain\Models\TalentApplication;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\InterviewStatusEnum;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\TalentApplicationStatusEnum;
use App\Modules\V1\Talents\Domain\Models\Talent;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Database\Seeders\AdminRoleSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\TestCase;

class TalentApplicationFinalStateWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_interview_reaches_acceptance_and_blacklist_actions_preserve_the_application_decision(): void
    {
        $this->seed([PermissionSeeder::class, AdminRoleSeeder::class]);
        $this->app->instance(InterviewCalendarGatewayInterface::class, new FinalStateFakeCalendarGateway);

        $admin = $this->createAdmin('platform.admin@example.test');
        $otherAdmin = $this->createAdmin('other.admin@example.test');
        [$talent, $application] = $this->createTalentApplication();
        $review = app(TalentApplicationReviewWorkflow::class);
        $review->startReview($application->id, $admin);
        $review->saveEvaluation($application->id, [
            'technical_skills_rating' => 4,
            'communication_skills_rating' => 4,
            'problem_solving_rating' => 4,
            'soft_skills_rating' => 4,
            'tips' => [],
        ], $admin);

        $start = now()->addHour()->startOfMinute();
        $interview = $review->scheduleInterview($application->id, [
            'type' => 'technical',
            'interviewer_admin_ids' => [$admin->id],
            'slots' => [['mode' => 'online', 'start' => $start->toISOString()]],
        ], $admin);
        $slot = $interview->slots->firstOrFail();
        app(TalentInterviewWorkflow::class)->selectSlot($talent->load('user'), $slot->id);

        Carbon::setTestNow($start);
        $inProgress = app(InterviewStateSynchronizer::class)->synchronize($interview);
        $this->assertSame(InterviewStatusEnum::IN_PROGRESS, $inProgress->status);
        $this->assertDatabaseHas('talent_application_events', [
            'application_id' => $application->id,
            'admin_id' => $admin->id,
            'action' => 'interview_started',
        ]);

        Carbon::setTestNow($slot->ends_at);
        $completed = app(InterviewStateSynchronizer::class)->synchronize($interview);
        $this->assertSame(InterviewStatusEnum::COMPLETED, $completed->status);

        $workflow = app(AdminInterviewWorkflow::class);
        try {
            $workflow->saveEvaluation($interview->id, [
                'technical_skills_rating' => 5,
                'communication_skills_rating' => 4,
                'problem_solving_rating' => 5,
                'soft_skills_rating' => 4,
                'final_feedback' => 'Must not be saved by another admin.',
            ], $otherAdmin);
            $this->fail('A non-responsible admin managed the interview.');
        } catch (ConflictHttpException $exception) {
            $this->assertSame(__('talents.interview.responsible_admin_required'), $exception->getMessage());
        }

        $workflow->saveEvaluation($interview->id, [
            'technical_skills_rating' => 5,
            'communication_skills_rating' => 4,
            'problem_solving_rating' => 5,
            'soft_skills_rating' => 4,
            'hr_report' => null,
            'technical_report' => 'Strong technical performance.',
            'psychology_report' => null,
            'final_feedback' => 'Recommended for acceptance.',
        ], $admin);
        $accepted = $workflow->accept($application->id, $admin);
        $this->assertSame(TalentApplicationStatusEnum::ACCEPTED, $accepted->status);

        $states = app(AdminTalentStateService::class);
        $blacklisted = $states->addToBlacklist($talent->id, 'Policy violation.', $admin);
        $this->assertSame('accepted', $blacklisted->status->value);
        $this->assertSame(['remove_from_blacklist'], $blacklisted->available_actions);
        $this->assertDatabaseHas('talent_blacklist_entries', [
            'application_id' => $application->id,
            'removed_at' => null,
        ]);

        $restored = $states->removeFromBlacklist($talent->id, null, $admin);
        $this->assertSame('accepted', $restored->status->value);
        $this->assertContains('add_to_blacklist', $restored->available_actions);
        $this->assertDatabaseMissing('talent_blacklist_entries', [
            'application_id' => $application->id,
            'removed_at' => null,
        ]);

        Carbon::setTestNow();
    }

    private function createAdmin(string $email): Admin
    {
        $user = User::query()->create([
            'first_name' => 'Platform',
            'last_name' => 'Admin',
            'email' => $email,
            'password' => 'Password123!',
            'type' => PortalTypeEnum::ADMIN,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $user->syncRoles([
            Role::query()->where('name', 'admin')->where('portal', PortalTypeEnum::ADMIN->value)->firstOrFail(),
        ]);

        return Admin::query()->create(['user_id' => $user->id]);
    }

    /** @return array{Talent, TalentApplication} */
    private function createTalentApplication(): array
    {
        $user = User::query()->create([
            'first_name' => 'Talent',
            'last_name' => 'Candidate',
            'email' => 'candidate@example.test',
            'password' => 'Password123!',
            'type' => PortalTypeEnum::TALENT,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $talent = Talent::query()->create(['user_id' => $user->id]);
        $application = TalentApplication::query()->create([
            'talent_id' => $talent->id,
            'status' => TalentApplicationStatusEnum::SUBMITTED,
            'submitted_at' => now(),
            'submission_snapshot' => ['user' => ['email' => $user->email]],
            'profile_completion_at_submission' => 100,
            'snapshot_version' => 1,
            'snapshot_captured_at' => now(),
        ]);

        return [$talent, $application];
    }
}

class FinalStateFakeCalendarGateway implements InterviewCalendarGatewayInterface
{
    public function createOnlineMeeting(InterviewSlot $slot, string $summary, array $attendeeEmails): CalendarMeetingData
    {
        return new CalendarMeetingData('final-state-event', 'https://meet.google.com/final-state');
    }

    public function deleteEvent(string $eventId): void {}
}
