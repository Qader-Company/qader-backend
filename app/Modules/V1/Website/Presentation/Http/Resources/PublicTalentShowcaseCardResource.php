<?php

namespace App\Modules\V1\Website\Presentation\Http\Resources;

use BackedEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicTalentShowcaseCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $seniority = $this->seniority_level instanceof BackedEnum
            ? (string) $this->seniority_level->value
            : ($this->seniority_level === null ? null : (string) $this->seniority_level);

        return [
            'talent_code' => $this->talent_code,
            'image' => asset('images/default-talent-avatar.svg'),
            'job_title' => $this->whenLoaded('jobTitle', fn () => $this->jobTitle ? [
                'id' => $this->jobTitle->id,
                'name' => $this->jobTitle->name,
            ] : null),
            'seniority_level' => $seniority === null ? null : [
                'value' => $seniority,
                'label' => __("enums.seniority_levels.{$seniority}"),
            ],
            'years_of_experience' => $this->years_of_experience,
            'country' => $this->whenLoaded('residenceCountry', fn () => $this->residenceCountry ? [
                'id' => $this->residenceCountry->id,
                'code' => $this->residenceCountry->code,
                'name' => $this->residenceCountry->name,
            ] : null),
            'top_skills' => $this->whenLoaded('skills', fn () => $this->skills
                ->take(3)
                ->map(fn ($skill): array => [
                    'id' => $skill->id,
                    'name' => $skill->name,
                ])
                ->values()),
            'indicative_salary' => [
                'minimum' => $this->expected_salary_min === null
                    ? null
                    : (float) $this->expected_salary_min,
                'maximum' => $this->expected_salary_max === null
                    ? null
                    : (float) $this->expected_salary_max,
                'currency' => $this->whenLoaded('expectedCurrency', fn () => $this->expectedCurrency ? [
                    'id' => $this->expectedCurrency->id,
                    'code' => $this->expectedCurrency->code,
                    'symbol' => $this->expectedCurrency->symbol,
                    'name' => $this->expectedCurrency->name,
                ] : null),
            ],
        ];
    }
}
