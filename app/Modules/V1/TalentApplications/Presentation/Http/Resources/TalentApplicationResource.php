<?php

namespace App\Modules\V1\TalentApplications\Presentation\Http\Resources;

use App\Modules\V1\TalentApplications\Domain\ValueObjects\InterviewStatusEnum;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\TalentApplicationScreenStateEnum;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\TalentApplicationStatusEnum;
use BackedEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TalentApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof BackedEnum ? $this->status->value : $this->status;

        return [
            'id' => $this->id,
            'status' => $status,
            'screen_state' => $this->screenState()->value,
            'submitted_at' => $this->submitted_at,
            'review_decided_at' => $this->review_decided_at,
            'reapply_at' => $this->reapply_at,
            'can_reapply' => $status === TalentApplicationStatusEnum::REJECTED->value
                && $this->blacklistEntry === null
                && $this->reapply_at?->isPast(),
            'is_blacklisted' => $this->blacklistEntry !== null
                && $this->blacklistEntry->removed_at === null,
            'profile_completion_at_submission' => $this->profile_completion_at_submission,
            'snapshot_version' => $this->snapshot_version,
            'snapshot_captured_at' => $this->snapshot_captured_at,
            'submitted_cv' => $this->when(
                $this->getFirstMedia('submitted_cv') !== null,
                fn () => [
                    'id' => $this->getFirstMedia('submitted_cv')->id,
                    'file_name' => $this->getFirstMedia('submitted_cv')->file_name,
                    'download_url' => route('talent.profile.media.download', [
                        'media' => $this->getFirstMedia('submitted_cv')->id,
                    ]),
                ],
            ),
            'interview' => $this->whenLoaded(
                'interview',
                fn () => $this->interview ? new TalentInterviewResource($this->interview) : null,
            ),
        ];
    }

    private function screenState(): TalentApplicationScreenStateEnum
    {
        return match ($this->status) {
            TalentApplicationStatusEnum::SUBMITTED,
            TalentApplicationStatusEnum::UNDER_REVIEW => TalentApplicationScreenStateEnum::UNDER_REVIEW,
            TalentApplicationStatusEnum::REJECTED => TalentApplicationScreenStateEnum::REJECTED,
            TalentApplicationStatusEnum::ACCEPTED => TalentApplicationScreenStateEnum::ACCEPTED,
            TalentApplicationStatusEnum::INTERVIEW_REQUIRED => match ($this->interview?->status) {
                InterviewStatusEnum::AWAITING_SLOT_SELECTION => TalentApplicationScreenStateEnum::INTERVIEW_SLOT_REQUIRED,
                InterviewStatusEnum::AWAITING_NEW_SLOTS => TalentApplicationScreenStateEnum::AWAITING_NEW_INTERVIEW_SLOTS,
                InterviewStatusEnum::CONFIRMED => TalentApplicationScreenStateEnum::INTERVIEW_CONFIRMED,
                InterviewStatusEnum::COMPLETED => TalentApplicationScreenStateEnum::INTERVIEW_COMPLETED,
                InterviewStatusEnum::CANCELLED => TalentApplicationScreenStateEnum::INTERVIEW_CANCELLED,
                default => TalentApplicationScreenStateEnum::INTERVIEW_SLOT_REQUIRED,
            },
        };
    }
}
