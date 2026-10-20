<?php

namespace App\Modules\V1\TalentApplications\Presentation\Http\Resources;

use App\Modules\V1\Currencies\Presentation\Http\Resources\CurrencyResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TalentEvaluationResource extends JsonResource
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
            'recommended_salary' => $this->recommended_salary_min === null ? null : [
                'min' => $this->recommended_salary_min,
                'max' => $this->recommended_salary_max,
                'currency' => new CurrencyResource($this->whenLoaded('currency')),
            ],
            'reports' => [
                'hr' => $this->hr_report,
                'technical' => $this->technical_report,
                'psychology' => $this->psychology_report,
            ],
            'tips' => $this->whenLoaded('tips', fn () => $this->tips->map(fn ($tip): array => [
                'id' => $tip->id,
                'title' => $tip->title,
                'priority' => $tip->priority->value,
                'description' => $tip->description,
                'display_order' => $tip->display_order,
            ])),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
