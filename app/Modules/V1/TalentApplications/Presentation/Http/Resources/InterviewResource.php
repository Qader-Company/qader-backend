<?php

namespace App\Modules\V1\TalentApplications\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InterviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'status' => $this->status->value,
            'interviewers' => AdminSummaryResource::collection($this->whenLoaded('admins')),
            'slots' => $this->whenLoaded('slots', fn () => $this->slots->map(fn ($slot): array => [
                'id' => $slot->id,
                'mode' => $slot->mode->value,
                'start' => $slot->starts_at?->toISOString(),
                'end' => $slot->ends_at?->toISOString(),
                'location' => $slot->location,
                'latitude' => $slot->latitude,
                'longitude' => $slot->longitude,
                'status' => $slot->status->value,
            ])),
            'google_calendar_event_id' => $this->google_calendar_event_id,
            'meeting_url' => $this->meeting_url,
            'created_at' => $this->created_at,
        ];
    }
}
