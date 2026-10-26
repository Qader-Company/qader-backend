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
            'round_number' => $this->round_number,
            'type' => $this->type->value,
            'status' => $this->status->value,
            'application_id' => $this->application_id,
            'application' => $this->whenLoaded('application', fn () => [
                'id' => $this->application->id,
                'status' => $this->application->status->value,
                'talent' => [
                    'id' => $this->application->talent_id,
                    'full_name' => trim(
                        ($this->application->talent?->user?->first_name ?? '').' '
                        .($this->application->talent?->user?->last_name ?? ''),
                    ),
                    'email' => $this->application->talent?->user?->email,
                ],
            ]),
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
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
            'evaluation' => $this->whenLoaded(
                'evaluation',
                fn () => $this->evaluation ? new InterviewEvaluationResource($this->evaluation) : null,
            ),
            'available_actions' => $this->available_actions ?? [],
            'created_at' => $this->created_at,
        ];
    }
}
