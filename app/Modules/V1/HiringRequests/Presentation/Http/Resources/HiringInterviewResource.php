<?php

namespace App\Modules\V1\HiringRequests\Presentation\Http\Resources;

use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringInterviewResponseEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringInterviewStatusEnum;
use App\Support\ApiMetadata\EnumLabel;
use BackedEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HiringInterviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $uri = (string) $request->route()?->uri();
        $portal = str_contains($uri, '/admin/') ? 'admin' : (str_contains($uri, '/client/') ? 'client' : 'talent');

        return [
            'id' => $this->id,
            'status' => $this->value($this->status),
            'status_label' => EnumLabel::for($this->status, 'hiring_interview_statuses'),
            'start' => $this->starts_at,
            'end' => $this->ends_at,
            'duration_minutes' => $this->starts_at?->diffInMinutes($this->ends_at),
            'client_response' => [
                'status' => $this->value($this->client_response),
                'status_label' => EnumLabel::for($this->client_response, 'hiring_interview_responses'),
                'reason' => $this->client_response_reason,
                'responded_at' => $this->client_responded_at,
            ],
            'talent_response' => [
                'status' => $this->value($this->talent_response),
                'status_label' => EnumLabel::for($this->talent_response, 'hiring_interview_responses'),
                'reason' => $this->talent_response_reason,
                'responded_at' => $this->talent_responded_at,
            ],
            'meeting_url' => $this->meeting_url,
            'can_join' => filled($this->meeting_url)
                && in_array($this->status, [HiringInterviewStatusEnum::CONFIRMED, HiringInterviewStatusEnum::IN_PROGRESS], true)
                && now()->betweenIncluded($this->starts_at, $this->ends_at),
            'available_actions' => $this->actions($portal),
            'proposed_at' => $this->proposed_at,
            'confirmed_at' => $this->confirmed_at,
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
        ];
    }

    private function actions(string $portal): array
    {
        if ($portal === 'admin') {
            return match ($this->status) {
                HiringInterviewStatusEnum::AWAITING_RESPONSES => ['respond_to_interview_on_behalf', 'reschedule_interview'],
                HiringInterviewStatusEnum::AWAITING_ADMIN_CONFIRMATION => ['confirm_interview', 'reschedule_interview'],
                HiringInterviewStatusEnum::AWAITING_RESCHEDULE => ['reschedule_interview'],
                HiringInterviewStatusEnum::CONFIRMED => now()->betweenIncluded($this->starts_at, $this->ends_at)
                    ? ['reschedule_interview', 'start_interview']
                    : ['reschedule_interview'],
                HiringInterviewStatusEnum::IN_PROGRESS => ['complete_interview'],
                default => [],
            };
        }
        $response = $portal === 'client' ? $this->client_response : $this->talent_response;

        if ($this->status === HiringInterviewStatusEnum::AWAITING_RESPONSES && $response === HiringInterviewResponseEnum::PENDING) {
            return ['accept_schedule', 'request_schedule_change', 'apologize'];
        }

        return $this->status === HiringInterviewStatusEnum::CONFIRMED && now()->lt($this->starts_at)
            ? ['request_schedule_change', 'apologize']
            : [];
    }

    private function value(mixed $value): mixed
    {
        return $value instanceof BackedEnum ? $value->value : $value;
    }
}
