<?php

namespace App\Modules\V1\TalentApplications\Application\Services;

use App\Modules\V1\TalentApplications\Application\Contracts\InterviewCalendarGatewayInterface;
use App\Modules\V1\TalentApplications\Domain\Models\Interview;
use App\Modules\V1\TalentApplications\Domain\Models\InterviewSlot;
use App\Modules\V1\TalentApplications\Domain\Models\TalentApplication;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\InterviewModeEnum;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\InterviewSlotStatusEnum;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\InterviewStatusEnum;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\TalentApplicationStatusEnum;
use App\Modules\V1\Talents\Domain\Models\Talent;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class TalentInterviewWorkflow
{
    public function __construct(
        private readonly InterviewCalendarGatewayInterface $calendar,
    ) {}

    public function selectSlot(Talent $talent, int $slotId): Interview
    {
        return DB::transaction(function () use ($talent, $slotId): Interview {
            $slot = InterviewSlot::query()->lockForUpdate()->findOrFail($slotId);
            [$application, $interview] = $this->lockOwnedInterview($talent, $slot->interview_id);

            if (
                $application->status !== TalentApplicationStatusEnum::INTERVIEW_REQUIRED
                || $interview->status !== InterviewStatusEnum::AWAITING_SLOT_SELECTION
                || $slot->status !== InterviewSlotStatusEnum::AVAILABLE
            ) {
                throw new ConflictHttpException(__('talents.interview.slot_not_selectable'));
            }

            if ($slot->starts_at->isPast()) {
                throw new ConflictHttpException(__('talents.interview.slot_expired'));
            }

            $meeting = null;
            if ($slot->mode === InterviewModeEnum::ONLINE) {
                $interview->loadMissing(['admins.user', 'application.talent.user']);
                $meeting = $this->calendar->createOnlineMeeting(
                    $slot,
                    __('talents.interview.calendar_title', [
                        'type' => ucfirst($interview->type->value),
                        'talent' => $talent->user->first_name.' '.$talent->user->last_name,
                    ]),
                    $interview->admins->pluck('user.email')
                        ->push($talent->user->email)
                        ->filter()
                        ->values()
                        ->all(),
                );
            }

            $interview->slots()
                ->whereKeyNot($slot->id)
                ->where('status', InterviewSlotStatusEnum::AVAILABLE->value)
                ->update(['status' => InterviewSlotStatusEnum::EXPIRED->value]);
            $slot->update(['status' => InterviewSlotStatusEnum::SELECTED]);
            $interview->update([
                'status' => InterviewStatusEnum::CONFIRMED,
                'google_calendar_event_id' => $meeting?->eventId,
                'meeting_url' => $meeting?->meetingUrl,
                'rejected_slots_reason' => null,
            ]);

            return $this->loadForTalent($interview->refresh());
        });
    }

    public function rejectAllSlots(Talent $talent, string $reason): Interview
    {
        return DB::transaction(function () use ($talent, $reason): Interview {
            $application = TalentApplication::query()
                ->where('talent_id', $talent->id)
                ->lockForUpdate()
                ->firstOrFail();
            $interview = Interview::query()
                ->where('application_id', $application->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($interview->slot_rejection_count >= Interview::MAX_SLOT_REJECTIONS) {
                throw new ConflictHttpException(__('talents.interview.slot_rejection_limit_reached'));
            }

            if (
                $application->status !== TalentApplicationStatusEnum::INTERVIEW_REQUIRED
                || $interview->status !== InterviewStatusEnum::AWAITING_SLOT_SELECTION
                || ! $interview->slots()->where('status', InterviewSlotStatusEnum::AVAILABLE->value)->exists()
            ) {
                throw new ConflictHttpException(__('talents.interview.slots_not_rejectable'));
            }

            $interview->slots()
                ->where('status', InterviewSlotStatusEnum::AVAILABLE->value)
                ->update(['status' => InterviewSlotStatusEnum::REJECTED_BY_TALENT->value]);
            $interview->update([
                'status' => InterviewStatusEnum::AWAITING_NEW_SLOTS,
                'rejected_slots_reason' => $reason,
                'slot_rejection_count' => $interview->slot_rejection_count + 1,
            ]);

            return $this->loadForTalent($interview->refresh());
        });
    }

    public function cancelConfirmedSlot(Talent $talent): Interview
    {
        return DB::transaction(function () use ($talent): Interview {
            $application = TalentApplication::query()
                ->where('talent_id', $talent->id)
                ->lockForUpdate()
                ->firstOrFail();
            $interview = Interview::query()
                ->where('application_id', $application->id)
                ->lockForUpdate()
                ->firstOrFail();
            $selectedSlot = $interview->slots()
                ->where('status', InterviewSlotStatusEnum::SELECTED->value)
                ->lockForUpdate()
                ->first();

            if (
                $application->status !== TalentApplicationStatusEnum::INTERVIEW_REQUIRED
                || $interview->status !== InterviewStatusEnum::CONFIRMED
                || $interview->talent_reschedule_used_at !== null
                || $selectedSlot === null
                || $selectedSlot->starts_at->isPast()
            ) {
                throw new ConflictHttpException(__('talents.interview.cancellation_not_allowed'));
            }

            if ($interview->google_calendar_event_id) {
                $this->calendar->deleteEvent($interview->google_calendar_event_id);
            }

            $selectedSlot->update(['status' => InterviewSlotStatusEnum::CANCELLED]);
            $interview->update([
                'status' => InterviewStatusEnum::AWAITING_NEW_SLOTS,
                'google_calendar_event_id' => null,
                'meeting_url' => null,
                'talent_reschedule_used_at' => now(),
            ]);

            return $this->loadForTalent($interview->refresh());
        });
    }

    /** @return array{TalentApplication, Interview} */
    private function lockOwnedInterview(Talent $talent, int $interviewId): array
    {
        $interview = Interview::query()->lockForUpdate()->findOrFail($interviewId);
        $application = TalentApplication::query()
            ->whereKey($interview->application_id)
            ->where('talent_id', $talent->id)
            ->lockForUpdate()
            ->firstOrFail();

        return [$application, $interview];
    }

    private function loadForTalent(Interview $interview): Interview
    {
        return $interview->load(['slots', 'admins.user']);
    }
}
