<?php

namespace Tests\Feature;

use App\Modules\V1\AccessControl\Domain\Models\Role;
use App\Modules\V1\Admins\Domain\Models\Admin;
use App\Modules\V1\TalentApplications\Application\Contracts\InterviewCalendarGatewayInterface;
use App\Modules\V1\TalentApplications\Application\Data\CalendarMeetingData;
use App\Modules\V1\TalentApplications\Application\Services\TalentApplicationReviewWorkflow;
use App\Modules\V1\TalentApplications\Application\Services\TalentInterviewWorkflow;
use App\Modules\V1\TalentApplications\Domain\Models\Interview;
use App\Modules\V1\TalentApplications\Domain\Models\InterviewSlot;
use App\Modules\V1\TalentApplications\Domain\Models\TalentApplication;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\InterviewSlotStatusEnum;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\InterviewStatusEnum;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\TalentApplicationStatusEnum;
use App\Modules\V1\Talents\Domain\Models\Talent;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Database\Seeders\AdminRoleSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\TestCase;

class TalentInterviewWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private FakeInterviewCalendarGateway $calendar;

    private TalentApplicationReviewWorkflow $adminWorkflow;

    private TalentInterviewWorkflow $talentWorkflow;

    private Admin $reviewer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, AdminRoleSeeder::class]);
        $this->calendar = new FakeInterviewCalendarGateway;
        $this->app->instance(InterviewCalendarGatewayInterface::class, $this->calendar);
        $this->adminWorkflow = app(TalentApplicationReviewWorkflow::class);
        $this->talentWorkflow = app(TalentInterviewWorkflow::class);
        $this->reviewer = $this->createReviewer();
    }

    public function test_talent_selects_an_online_slot_and_google_meet_is_saved(): void
    {
        [$talent, $interview] = $this->createScheduledInterview();
        $selected = $interview->slots->first();

        $result = $this->talentWorkflow->selectSlot($talent, $selected->id);

        $this->assertSame(InterviewStatusEnum::CONFIRMED, $result->status);
        $this->assertSame('calendar-event-1', $result->google_calendar_event_id);
        $this->assertSame('https://meet.google.com/demo-room', $result->meeting_url);
        $this->assertDatabaseHas('interview_slots', [
            'id' => $selected->id,
            'status' => InterviewSlotStatusEnum::SELECTED->value,
        ]);
        $this->assertSame(1, InterviewSlot::query()
            ->where('interview_id', $interview->id)
            ->where('status', InterviewSlotStatusEnum::EXPIRED->value)
            ->count());
        $this->assertCount(1, $this->calendar->createdSlots);
    }

    public function test_talent_selects_an_online_slot_using_manual_meeting_url_without_google_calendar(): void
    {
        [$talent, $interview] = $this->createScheduledInterview('https://meet.google.com/manual-room');
        $selected = $interview->slots->first();

        $result = $this->talentWorkflow->selectSlot($talent, $selected->id);

        $this->assertSame(InterviewStatusEnum::CONFIRMED, $result->status);
        $this->assertNull($result->google_calendar_event_id);
        $this->assertSame('https://meet.google.com/manual-room', $result->meeting_url);
        $this->assertSame([], $this->calendar->createdSlots);
    }

    public function test_talent_can_reject_all_available_slots_with_a_reason(): void
    {
        [$talent, $interview] = $this->createScheduledInterview();

        $result = $this->talentWorkflow->rejectAllSlots(
            $talent,
            'I am unavailable on all offered dates.',
        );

        $this->assertSame(InterviewStatusEnum::AWAITING_NEW_SLOTS, $result->status);
        $this->assertSame('I am unavailable on all offered dates.', $result->rejected_slots_reason);
        $this->assertSame(1, $result->slot_rejection_count);
        $this->assertSame(2, InterviewSlot::query()
            ->where('interview_id', $interview->id)
            ->where('status', InterviewSlotStatusEnum::REJECTED_BY_TALENT->value)
            ->count());
    }

    public function test_talent_cannot_reject_offered_slots_more_than_twice(): void
    {
        [$talent, $interview] = $this->createScheduledInterview();
        $this->talentWorkflow->rejectAllSlots($talent, 'The first offered times do not work for me.');

        $this->offerReplacementSlot($interview, 4);
        $second = $this->talentWorkflow->rejectAllSlots($talent, 'The replacement time does not work for me.');
        $this->assertSame(2, $second->slot_rejection_count);

        $this->offerReplacementSlot($interview, 6);

        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage(__('talents.interview.slot_rejection_limit_reached'));
        $this->talentWorkflow->rejectAllSlots($talent, 'A third rejection must not be accepted.');
    }

    public function test_talent_can_cancel_a_confirmed_interview_only_once(): void
    {
        [$talent, $interview] = $this->createScheduledInterview();
        $selected = $interview->slots->first();
        $this->talentWorkflow->selectSlot($talent, $selected->id);

        $cancelled = $this->talentWorkflow->cancelConfirmedSlot($talent);

        $this->assertSame(InterviewStatusEnum::AWAITING_NEW_SLOTS, $cancelled->status);
        $this->assertNotNull($cancelled->talent_reschedule_used_at);
        $this->assertNull($cancelled->meeting_url);
        $this->assertSame(['calendar-event-1'], $this->calendar->deletedEventIds);
        $this->assertDatabaseHas('interview_slots', [
            'id' => $selected->id,
            'status' => InterviewSlotStatusEnum::CANCELLED->value,
        ]);

        $this->expectException(ConflictHttpException::class);
        $this->talentWorkflow->cancelConfirmedSlot($talent);
    }

    /** @return array{Talent, Interview} */
    private function createScheduledInterview(?string $meetingUrl = null): array
    {
        $user = User::query()->create([
            'first_name' => 'Talent',
            'last_name' => 'Person',
            'email' => 'talent'.Talent::query()->count().'@example.test',
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

        $this->adminWorkflow->startReview($application->id, $this->reviewer);
        $this->adminWorkflow->saveEvaluation($application->id, [
            'technical_skills_rating' => 4,
            'communication_skills_rating' => 4,
            'problem_solving_rating' => 4,
            'soft_skills_rating' => 4,
            'tips' => [],
        ], $this->reviewer);
        $start = now()->addDays(2)->startOfHour();
        $interview = $this->adminWorkflow->scheduleInterview($application->id, [
            'type' => 'technical',
            'interviewer_admin_ids' => [$this->reviewer->id],
            'meeting_url' => $meetingUrl,
            'slots' => [
                ['mode' => 'online', 'start' => $start->toISOString()],
                ['mode' => 'online', 'start' => $start->copy()->addHours(2)->toISOString()],
            ],
        ], $this->reviewer);

        return [$talent->load('user'), $interview];
    }

    private function createReviewer(): Admin
    {
        $user = User::query()->create([
            'first_name' => 'Review',
            'last_name' => 'Admin',
            'email' => 'reviewer@example.test',
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

    private function offerReplacementSlot(Interview $interview, int $daysFromNow): void
    {
        $this->adminWorkflow->replaceInterviewSlots($interview->application_id, [
            'slots' => [[
                'mode' => 'online',
                'start' => now()->addDays($daysFromNow)->startOfHour()->toISOString(),
            ]],
        ], $this->reviewer);
    }
}

class FakeInterviewCalendarGateway implements InterviewCalendarGatewayInterface
{
    /** @var array<int, int> */
    public array $createdSlots = [];

    /** @var array<int, string> */
    public array $deletedEventIds = [];

    public function createOnlineMeeting(
        InterviewSlot $slot,
        string $summary,
        array $attendeeEmails,
    ): CalendarMeetingData {
        $this->createdSlots[] = $slot->id;

        return new CalendarMeetingData(
            'calendar-event-'.count($this->createdSlots),
            'https://meet.google.com/demo-room',
        );
    }

    public function deleteEvent(string $eventId): void
    {
        $this->deletedEventIds[] = $eventId;
    }
}
