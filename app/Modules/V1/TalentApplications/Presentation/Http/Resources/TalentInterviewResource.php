<?php

namespace App\Modules\V1\TalentApplications\Presentation\Http\Resources;

use App\Modules\V1\TalentApplications\Domain\ValueObjects\InterviewModeEnum;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\InterviewSlotStatusEnum;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\InterviewStatusEnum;
use App\Support\ApiMetadata\EnumLabel;
use App\Settings\TalentEvaluationSettings;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TalentInterviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $settings = app(TalentEvaluationSettings::class);
        $slotRejectionLimit = $settings->interview_slot_rejection_limit;
        $rescheduleLimit = $settings->confirmed_interview_reschedule_limit;
        $slots = $this->relationLoaded('slots') ? $this->slots : collect();
        $selectedSlot = $slots->first(
            fn ($slot) => $slot->status === InterviewSlotStatusEnum::SELECTED,
        );
        $availableSlots = $slots->filter(
            fn ($slot) => $slot->status === InterviewSlotStatusEnum::AVAILABLE
                && $slot->starts_at->isFuture(),
        )->values();
        $joinWindowStartsAt = $selectedSlot?->starts_at?->copy()->subMinutes(15);
        $joinWindowEndsAt = $selectedSlot?->ends_at;
        $canJoin = $selectedSlot !== null
            && $selectedSlot->mode === InterviewModeEnum::ONLINE
            && filled($this->meeting_url)
            && ! in_array($this->status, [InterviewStatusEnum::CANCELLED, InterviewStatusEnum::COMPLETED], true)
            && now()->greaterThanOrEqualTo($joinWindowStartsAt)
            && now()->lessThan($joinWindowEndsAt);

        return [
            'id' => $this->id,
            'round_number' => $this->round_number,
            'type' => $this->type->value,
            'status' => $this->status->value,
            'status_label' => EnumLabel::for($this->status, 'interview_statuses'),
            'available_slots' => TalentInterviewSlotResource::collection($availableSlots),
            'selected_slot' => $selectedSlot ? new TalentInterviewSlotResource($selectedSlot) : null,
            'meeting_url' => in_array($this->status, [
                InterviewStatusEnum::CONFIRMED,
                InterviewStatusEnum::IN_PROGRESS,
            ], true) ? $this->meeting_url : null,
            'join_window_starts_at' => $joinWindowStartsAt,
            'join_window_ends_at' => $joinWindowEndsAt,
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
            'slot_rejections' => [
                'used' => $this->slot_rejection_count,
                'maximum' => $slotRejectionLimit,
                'remaining' => max(0, $slotRejectionLimit - $this->slot_rejection_count),
            ],
            'talent_reschedules' => [
                'used' => $this->talent_reschedule_count,
                'maximum' => $rescheduleLimit,
                'remaining' => max(0, $rescheduleLimit - $this->talent_reschedule_count),
            ],
            'talent_reschedule_used_at' => $this->talent_reschedule_used_at,
            'actions' => [
                'can_select_slot' => $this->status === InterviewStatusEnum::AWAITING_SLOT_SELECTION
                    && $availableSlots->isNotEmpty(),
                'can_reject_all_slots' => $this->status === InterviewStatusEnum::AWAITING_SLOT_SELECTION
                    && $this->slot_rejection_count < $slotRejectionLimit
                    && $availableSlots->isNotEmpty(),
                'can_cancel_and_reschedule' => $this->status === InterviewStatusEnum::CONFIRMED
                    && $this->talent_reschedule_count < $rescheduleLimit
                    && $selectedSlot?->starts_at->isFuture(),
                'can_join' => $canJoin,
            ],
            'created_at' => $this->created_at,
        ];
    }
}
