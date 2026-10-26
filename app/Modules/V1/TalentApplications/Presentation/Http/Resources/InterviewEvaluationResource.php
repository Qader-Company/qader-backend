<?php

namespace App\Modules\V1\TalentApplications\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InterviewEvaluationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'evaluator' => new AdminSummaryResource($this->whenLoaded('evaluator')),
            'ratings' => [
                'technical_skills' => $this->technical_skills_rating,
                'communication_skills' => $this->communication_skills_rating,
                'problem_solving' => $this->problem_solving_rating,
                'soft_skills' => $this->soft_skills_rating,
            ],
            'average_rating' => $this->averageRating(),
            'overall_score' => $this->overallScore(),
            'reports' => [
                'hr' => $this->hr_report,
                'technical' => $this->technical_report,
                'psychology' => $this->psychology_report,
            ],
            'final_feedback' => $this->final_feedback,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
