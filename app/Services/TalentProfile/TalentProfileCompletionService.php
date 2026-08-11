<?php

namespace App\Services\TalentProfile;

use App\Enums\TalentDocumentType;
use App\Models\Talent;

class TalentProfileCompletionService
{
    /**
     * @return array{
     *     percentage: int,
     *     is_submittable: bool,
     *     sections: array<string, array{percentage: int, is_complete: bool, missing: list<string>}>
     * }
     */
    public function calculate(Talent $talent): array
    {
        $talent->loadMissing(['user', 'workPreference']);
        $talent->loadCount(['languages', 'skills', 'experiences', 'workTypes', 'workModes']);

        $hasCurrentCv = $talent->documents()
            ->where('type', TalentDocumentType::Cv->value)
            ->where('is_current', true)
            ->exists();

        $sections = [
            'basic_info' => $this->score([
                'name_and_email' => [$this->allFilled(
                    $talent->user?->first_name,
                    $talent->user?->last_name,
                    $talent->user?->email,
                ), 5],
                'phone' => [$this->allFilled(
                    $talent->user?->phone_country_code,
                    $talent->user?->phone_number,
                ), 5],
                'personal_information' => [$this->allFilled(
                    $talent->date_of_birth,
                    $talent->gender,
                ), 5],
                'location' => [$this->allFilled(
                    $talent->nationality_country_id,
                    $talent->residence_country_id,
                    $talent->residence_city_id,
                ), 5],
                'languages' => [$talent->languages_count > 0, 5],
            ]),
            'skills_experience' => $this->score([
                'job_title' => [$talent->job_title_id !== null, 10],
                'seniority_level' => [$talent->seniority_level !== null, 5],
                'years_of_experience' => [$talent->years_of_experience !== null, 5],
                'skills' => [$talent->skills_count > 0, 10],
                'experience_history' => [
                    $talent->years_of_experience === 0 || $talent->experiences_count > 0,
                    10,
                ],
                'cv' => [$hasCurrentCv, 5],
            ]),
            'work_preferences' => $this->score([
                'work_types' => [$talent->work_types_count > 0, 5],
                'work_modes' => [$talent->work_modes_count > 0, 5],
                'availability' => [
                    $talent->workPreference?->notice_period_days !== null
                    || $talent->workPreference?->available_from !== null,
                    5,
                ],
                'expected_salary_range' => [$this->hasValidExpectedSalary($talent), 10],
                'salary_currency_and_period' => [$this->allFilled(
                    $talent->workPreference?->expected_currency_id,
                    $talent->workPreference?->salary_period,
                ), 5],
            ]),
        ];

        $percentage = array_sum(array_column($sections, 'earned'));
        $sections = array_map(
            fn (array $section): array => [
                'percentage' => $section['percentage'],
                'is_complete' => $section['is_complete'],
                'missing' => $section['missing'],
            ],
            $sections,
        );

        return [
            'percentage' => $percentage,
            'is_submittable' => collect($sections)->every(
                fn (array $section): bool => $section['is_complete'],
            ),
            'sections' => $sections,
        ];
    }

    /**
     * Recalculate and cache the profile summary used by admin listings and filters.
     *
     * @return array{
     *     percentage: int,
     *     is_submittable: bool,
     *     sections: array<string, array{percentage: int, is_complete: bool, missing: list<string>}>
     * }
     */
    public function refresh(Talent $talent): array
    {
        $result = $this->calculate($talent);

        $talent->updateQuietly([
            'profile_completion_percentage' => $result['percentage'],
            'profile_completion_details' => [
                'is_submittable' => $result['is_submittable'],
                'sections' => $result['sections'],
            ],
            'profile_completion_updated_at' => now(),
        ]);

        return $result;
    }

    /**
     * @param  array<string, array{0: bool, 1: int}>  $requirements
     * @return array{percentage: int, earned: int, is_complete: bool, missing: list<string>}
     */
    private function score(array $requirements): array
    {
        $available = array_sum(array_column($requirements, 1));
        $earned = 0;
        $missing = [];

        foreach ($requirements as $name => [$satisfied, $weight]) {
            if ($satisfied) {
                $earned += $weight;
            } else {
                $missing[] = $name;
            }
        }

        return [
            'percentage' => (int) round(($earned / $available) * 100),
            'earned' => $earned,
            'is_complete' => $missing === [],
            'missing' => $missing,
        ];
    }

    private function allFilled(mixed ...$values): bool
    {
        return collect($values)->every(
            fn (mixed $value): bool => $value !== null && $value !== '',
        );
    }

    private function hasValidExpectedSalary(Talent $talent): bool
    {
        $minimum = $talent->workPreference?->expected_salary_min;
        $maximum = $talent->workPreference?->expected_salary_max;

        return $minimum !== null
            && $maximum !== null
            && (float) $minimum >= 0
            && (float) $maximum >= (float) $minimum;
    }
}
