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
        $isBlacklisted = $this->blacklistEntry !== null && $this->blacklistEntry->removed_at === null;

        return [
            'id' => $this->id,
            'status' => $status,
            'application_status' => $status,
            'effective_status' => $isBlacklisted ? 'blacklisted' : $status,
            'screen_state' => $this->screenState()->value,
            'submitted_at' => $this->submitted_at,
            'review_decided_at' => $this->review_decided_at,
            'reapply_at' => $this->reapply_at,
            'can_reapply' => $status === TalentApplicationStatusEnum::REJECTED->value
                && ! $isBlacklisted
                && $this->reapply_at?->isPast() === true,
            'is_blacklisted' => $isBlacklisted,
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
            'evaluation' => $this->whenLoaded(
                'evaluation',
                fn () => $this->evaluation ? new TalentEvaluationResource($this->evaluation) : null,
            ),
            'interview' => $this->whenLoaded(
                'interview',
                fn () => $this->interview ? new TalentInterviewResource($this->interview) : null,
            ),
            'current_interview' => $this->whenLoaded(
                'interview',
                fn () => $this->interview ? new TalentInterviewResource($this->interview) : null,
            ),
            'interview_history' => $this->whenLoaded(
                'interviews',
                fn () => TalentInterviewResource::collection($this->interviews),
            ),
        ];
    }

    private function screenState(): TalentApplicationScreenStateEnum
    {
        if ($this->blacklistEntry !== null && $this->blacklistEntry->removed_at === null) {
            return TalentApplicationScreenStateEnum::BLACKLISTED;
        }

        return match ($this->status) {
            TalentApplicationStatusEnum::SUBMITTED,
            TalentApplicationStatusEnum::UNDER_REVIEW => TalentApplicationScreenStateEnum::UNDER_REVIEW,
            TalentApplicationStatusEnum::REJECTED => TalentApplicationScreenStateEnum::REJECTED,
            TalentApplicationStatusEnum::ACCEPTED => TalentApplicationScreenStateEnum::ACCEPTED,
            TalentApplicationStatusEnum::INTERVIEW_REQUIRED => match ($this->interview?->status) {
                InterviewStatusEnum::AWAITING_SLOT_SELECTION => TalentApplicationScreenStateEnum::INTERVIEW_SLOT_REQUIRED,
                InterviewStatusEnum::AWAITING_NEW_SLOTS => TalentApplicationScreenStateEnum::AWAITING_NEW_INTERVIEW_SLOTS,
                InterviewStatusEnum::CONFIRMED => TalentApplicationScreenStateEnum::INTERVIEW_CONFIRMED,
                InterviewStatusEnum::IN_PROGRESS => TalentApplicationScreenStateEnum::INTERVIEW_IN_PROGRESS,
                InterviewStatusEnum::COMPLETED => TalentApplicationScreenStateEnum::INTERVIEW_COMPLETED,
                InterviewStatusEnum::CANCELLED => TalentApplicationScreenStateEnum::INTERVIEW_CANCELLED,
                default => TalentApplicationScreenStateEnum::INTERVIEW_SLOT_REQUIRED,
            },
        };
    }
}
