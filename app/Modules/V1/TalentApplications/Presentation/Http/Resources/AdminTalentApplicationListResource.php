<?php

namespace App\Modules\V1\TalentApplications\Presentation\Http\Resources;

use BackedEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminTalentApplicationListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $snapshot = $this->submission_snapshot ?? [];
        $user = $snapshot['user'] ?? [];

        return [
            'id' => $this->id,
            'status' => $this->status instanceof BackedEnum ? $this->status->value : $this->status,
            'submitted_at' => $this->submitted_at,
            'talent' => [
                'id' => $this->talent_id,
                'full_name' => trim(($user['first_name'] ?? '').' '.($user['last_name'] ?? '')),
                'email' => $user['email'] ?? null,
                'professional_field_id' => $snapshot['professional_field_id'] ?? null,
            ],
            'reviewer' => $this->assignedAdmin === null
                ? null
                : new AdminSummaryResource($this->assignedAdmin),
            'review_decided_at' => $this->review_decided_at,
        ];
    }
}
