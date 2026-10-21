<?php

namespace App\Modules\V1\TalentApplications\Presentation\Http\Resources;

use App\Modules\V1\TalentApplications\Domain\ValueObjects\InterviewSlotStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TalentInterviewSlotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'mode' => $this->mode->value,
            'start' => $this->starts_at?->toISOString(),
            'end' => $this->ends_at?->toISOString(),
            'duration_minutes' => (int) $this->starts_at?->diffInMinutes($this->ends_at),
            'location' => $this->location,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => $this->status->value,
            'is_selectable' => $this->status === InterviewSlotStatusEnum::AVAILABLE
                && $this->starts_at?->isFuture(),
        ];
    }
}
