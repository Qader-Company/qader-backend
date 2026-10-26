<?php

namespace App\Modules\V1\TalentApplications\Presentation\Http\Resources;

use App\Modules\V1\TalentApplications\Domain\ValueObjects\TalentApplicationStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminTalentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $activeBlacklist = $this->blacklistEntry?->removed_at === null ? $this->blacklistEntry : null;
        $applicationStatus = $this->status instanceof TalentApplicationStatusEnum
            ? $this->status->value
            : (string) $this->status;

        return [
            'id' => $this->talent_id,
            'application_id' => $this->id,
            'application_status' => $applicationStatus,
            'effective_status' => $activeBlacklist ? 'blacklisted' : $applicationStatus,
            'talent' => [
                'full_name' => trim(($this->talent?->user?->first_name ?? '').' '.($this->talent?->user?->last_name ?? '')),
                'email' => $this->talent?->user?->email,
                'phone_country_code' => $this->talent?->phone_country_code,
                'phone_number' => $this->talent?->phone_number,
                'professional_field_id' => $this->talent?->professional_field_id,
                'job_title_id' => $this->talent?->job_title_id,
                'seniority_level' => $this->talent?->seniority_level?->value,
                'years_of_experience' => $this->talent?->years_of_experience,
            ],
            'decision' => [
                'decided_at' => $this->review_decided_at,
                'reapply_at' => $this->reapply_at,
                'rejection_stage' => $applicationStatus !== 'rejected'
                    ? null
                    : ($this->interviews->isEmpty() ? 'before_interview' : 'after_interview'),
            ],
            'blacklist' => $activeBlacklist === null ? null : [
                'reason' => $activeBlacklist->blacklist_reason,
                'blocked_at' => $activeBlacklist->blocked_at,
                'blocked_by_admin_id' => $activeBlacklist->blocked_by_admin_id,
            ],
            'available_actions' => $this->available_actions ?? [],
        ];
    }
}
