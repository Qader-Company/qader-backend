<?php

namespace App\Modules\V1\TalentMarketplace\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TalentExpertOpinionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $profileReview = $this->evaluation;
        $latestInterview = $this->interviews
            ->filter(fn ($interview): bool => $interview->evaluation !== null)
            ->last();
        $review = $latestInterview?->evaluation ?? $profileReview;
        $currency = $profileReview?->currency;

        return [
            'talent_code' => $this->talent->talent_code,
            'ratings' => $review === null ? null : [
                'technical_skills' => $review->technical_skills_rating,
                'communication_skills' => $review->communication_skills_rating,
                'problem_solving' => $review->problem_solving_rating,
                'soft_skills' => $review->soft_skills_rating,
            ],
            'average_rating' => $review?->averageRating(),
            'recommended_salary' => $profileReview?->recommended_salary_min === null ? null : [
                'min' => $profileReview->recommended_salary_min,
                'max' => $profileReview->recommended_salary_max,
                'currency' => $currency === null ? null : [
                    'id' => $currency->id,
                    'code' => $currency->code,
                    'symbol' => $currency->symbol,
                    'name' => $currency->name,
                ],
            ],
            'reports' => $review === null ? null : [
                'hr' => $review->hr_report,
                'technical' => $review->technical_report,
                'psychology' => $review->psychology_report,
            ],
            'final_feedback' => $latestInterview?->evaluation?->final_feedback,
            'tips' => $profileReview === null ? [] : $profileReview->tips
                ->map(fn ($tip): array => [
                    'title' => $tip->title,
                    'priority' => $tip->priority->value,
                    'description' => $tip->description,
                    'display_order' => $tip->display_order,
                ])
                ->values(),
        ];
    }
}
