<?php

namespace App\Modules\V1\TalentApplications\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TalentEvaluationPageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'application_id' => $this->id,
            'profile_review' => $this->evaluation
                ? new TalentEvaluationResource($this->evaluation)
                : null,
            'interview_reviews' => $this->interviews
                ->filter(fn ($interview): bool => $interview->evaluation !== null)
                ->map(fn ($interview): array => [
                    'interview_id' => $interview->id,
                    'round_number' => $interview->round_number,
                    'type' => $interview->type->value,
                    'completed_at' => $interview->completed_at,
                    'evaluation' => new InterviewEvaluationResource($interview->evaluation),
                ])
                ->values(),
        ];
    }
}
