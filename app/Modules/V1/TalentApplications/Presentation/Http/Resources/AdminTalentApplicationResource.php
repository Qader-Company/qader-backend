<?php

namespace App\Modules\V1\TalentApplications\Presentation\Http\Resources;

use BackedEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminTalentApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $cv = $this->getFirstMedia('submitted_cv');

        return [
            'id' => $this->id,
            'status' => $this->status instanceof BackedEnum ? $this->status->value : $this->status,
            'submitted_at' => $this->submitted_at,
            'profile_completion_at_submission' => $this->profile_completion_at_submission,
            'snapshot_version' => $this->snapshot_version,
            'snapshot_captured_at' => $this->snapshot_captured_at,
            'submission_snapshot' => $this->submission_snapshot,
            'submitted_cv' => $cv === null ? null : [
                'id' => $cv->id,
                'file_name' => $cv->file_name,
                'download_url' => route('admin.talent-applications.cv.download', ['application' => $this->id]),
            ],
            'reviewer' => $this->assignedAdmin === null
                ? null
                : new AdminSummaryResource($this->assignedAdmin),
            'evaluation' => $this->evaluation === null
                ? null
                : new TalentEvaluationResource($this->evaluation),
            'interview' => $this->interview === null
                ? null
                : new InterviewResource($this->interview),
            'rejection' => $this->statusValue() !== 'rejected' ? null : [
                'decided_at' => $this->review_decided_at,
                'reapply_at' => $this->reapply_at,
                'blacklisted' => $this->blacklistEntry !== null && $this->blacklistEntry->removed_at === null,
                'blacklist_reason' => $this->blacklistEntry?->blacklist_reason,
            ],
            'available_actions' => $this->available_actions ?? [],
        ];
    }

    private function statusValue(): string
    {
        return $this->status instanceof BackedEnum ? $this->status->value : $this->status;
    }
}
