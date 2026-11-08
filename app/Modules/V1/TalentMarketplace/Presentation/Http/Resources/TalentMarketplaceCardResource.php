<?php

namespace App\Modules\V1\TalentMarketplace\Presentation\Http\Resources;

use BackedEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TalentMarketplaceCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $seniority = $this->enumValue($this->seniority_level);

        return [
            'id' => $this->id,
            'talent_code' => $this->talent_code,
            'job_title' => $this->whenLoaded('jobTitle', fn () => $this->jobTitle ? [
                'id' => $this->jobTitle->id,
                'name' => $this->jobTitle->name,
            ] : null),
            'professional_field' => $this->whenLoaded('professionalField', fn () => $this->professionalField ? [
                'id' => $this->professionalField->id,
                'slug' => $this->professionalField->slug,
                'name' => $this->professionalField->name,
            ] : null),
            'seniority_level' => $seniority === null ? null : [
                'value' => $seniority,
                'label' => __("enums.seniority_levels.{$seniority}"),
            ],
            'years_of_experience' => $this->years_of_experience,
            'location' => [
                'country' => $this->whenLoaded('residenceCountry', fn () => $this->residenceCountry ? [
                    'id' => $this->residenceCountry->id,
                    'code' => $this->residenceCountry->code,
                    'name' => $this->residenceCountry->name,
                ] : null),
                'city' => $this->whenLoaded('residenceCity', fn () => $this->residenceCity ? [
                    'id' => $this->residenceCity->id,
                    'name' => $this->residenceCity->name,
                ] : null),
            ],
            'work_types' => $this->whenLoaded('workTypes', fn () => $this->workTypes
                ->map(fn ($item): array => $this->enumOption('work_types', $item->work_type))
                ->values()),
            'work_modes' => $this->whenLoaded('workModes', fn () => $this->workModes
                ->map(fn ($item): array => $this->enumOption('work_modes', $item->work_mode))
                ->values()),
            'indicative_salary' => [
                'minimum' => $this->expected_salary_min === null ? null : (float) $this->expected_salary_min,
                'maximum' => $this->expected_salary_max === null ? null : (float) $this->expected_salary_max,
                'currency' => $this->whenLoaded('expectedCurrency', fn () => $this->expectedCurrency ? [
                    'id' => $this->expectedCurrency->id,
                    'code' => $this->expectedCurrency->code,
                    'symbol' => $this->expectedCurrency->symbol,
                    'name' => $this->expectedCurrency->name,
                ] : null),
            ],
            'top_skills' => $this->whenLoaded('skills', fn () => $this->skills
                ->take(5)
                ->map(fn ($skill): array => ['id' => $skill->id, 'name' => $skill->name])
                ->values()),
            'is_selected' => (bool) ($this->is_selected ?? false),
            'available_actions' => $this->available_actions ?? ['view_profile'],
        ];
    }

    protected function enumOption(string $translationKey, mixed $value): array
    {
        $value = $this->enumValue($value);

        return [
            'value' => $value,
            'label' => __("enums.{$translationKey}.{$value}"),
        ];
    }

    protected function enumValue(mixed $value): ?string
    {
        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        return $value === null ? null : (string) $value;
    }
}
