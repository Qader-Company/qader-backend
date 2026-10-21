<?php

namespace App\Modules\V1\TalentApplications\Presentation\Http\Resources;

use App\Modules\V1\TalentApplications\Domain\Models\Interview;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\InterviewSlotStatusEnum;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\InterviewStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TalentInterviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $slots = $this->relationLoaded('slots') ? $this->slots : collect();
        $selectedSlot = $slots->first(
            fn ($slot) => $slot->status === InterviewSlotStatusEnum::SELECTED,
        );
        $availableSlots = $slots->filter(
            fn ($slot) => $slot->status === InterviewSlotStatusEnum::AVAILABLE
                && $slot->starts_at->isFuture(),
        )->values();

        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'status' => $this->status->value,
            'available_slots' => TalentInterviewSlotResource::collection($availableSlots),
            'selected_slot' => $selectedSlot ? new TalentInterviewSlotResource($selectedSlot) : null,
            'meeting_url' => $this->status === InterviewStatusEnum::CONFIRMED
                ? $this->meeting_url
                : null,
            'slot_rejections' => [
                'used' => $this->slot_rejection_count,
                'maximum' => Interview::MAX_SLOT_REJECTIONS,
                'remaining' => max(0, Interview::MAX_SLOT_REJECTIONS - $this->slot_rejection_count),
            ],
            'talent_reschedule_used_at' => $this->talent_reschedule_used_at,
            'actions' => [
                'can_select_slot' => $this->status === InterviewStatusEnum::AWAITING_SLOT_SELECTION
                    && $availableSlots->isNotEmpty(),
                'can_reject_all_slots' => $this->status === InterviewStatusEnum::AWAITING_SLOT_SELECTION
                    && $this->slot_rejection_count < Interview::MAX_SLOT_REJECTIONS
                    && $availableSlots->isNotEmpty(),
                'can_cancel_and_reschedule' => $this->status === InterviewStatusEnum::CONFIRMED
                    && $this->talent_reschedule_used_at === null
                    && $selectedSlot?->starts_at->isFuture(),
                'can_join' => $this->status === InterviewStatusEnum::CONFIRMED
                    && filled($this->meeting_url),
            ],
            'created_at' => $this->created_at,
        ];
    }
}
