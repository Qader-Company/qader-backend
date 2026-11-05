<?php

namespace App\Modules\V1\Talents\Application\Services;

use App\Modules\V1\Cities\Domain\Models\City;
use App\Modules\V1\Countries\Domain\Models\Country;
use App\Modules\V1\JobTitles\Domain\Models\JobTitle;
use App\Modules\V1\Languages\Domain\Models\Language;
use App\Modules\V1\Skills\Domain\Models\Skill;
use App\Modules\V1\Talents\Domain\Models\Talent;
use App\Modules\V1\Tools\Domain\Models\Tool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class ResumeAutocompleteMapper
{
    /** @return array<string, mixed> */
    public function map(array $parsed, Talent $talent): array
    {
        $country = $this->matchTranslatedModel(
            Country::query()->where('is_active', true)->with('translations')->get(),
            $this->string($parsed, ['residence_country', 'candidate_country', 'country']),
        );
        $cityQuery = City::query()->where('is_active', true)->with('translations');
        if ($country !== null) {
            $cityQuery->where('country_id', $country->getKey());
        }
        $city = $this->matchTranslatedModel(
            $cityQuery->get(),
            $this->string($parsed, ['residence_city', 'candidate_city', 'city']),
        );

        $positionItems = $this->array($parsed, ['positions', 'work_experience', 'experience']);
        $projectItems = $this->array($parsed, ['projects']);
        $rawJobTitle = $this->string($parsed, ['job_title', 'candidate_job_title'])
            ?? $this->string($positionItems[0] ?? [], ['position_name', 'job_title', 'title']);
        $jobTitle = $this->matchNamedModel(
            $this->scopedQuery(JobTitle::query()->where('is_active', true), $talent)->get(),
            $rawJobTitle,
        );

        $rawSkills = $this->uniqueStrings([
            ...$this->array($parsed, ['skills', 'candidate_skills']),
            ...collect($positionItems)->flatMap(fn (mixed $position): array => is_array($position)
                ? $this->array($position, ['skills'])
                : [])->all(),
        ]);
        $rawTools = $this->uniqueStrings($this->array($parsed, ['tools', 'candidate_tools', 'technologies']));
        $rawLanguages = $this->uniqueStrings($this->array($parsed, ['languages', 'candidate_spoken_languages']));

        $skills = $this->matchNamedValues(
            $rawSkills,
            $this->scopedQuery(Skill::query()->where('is_active', true), $talent)->get(),
        );
        $tools = $this->matchNamedValues(
            $rawTools,
            $this->scopedQuery(Tool::query()->where('is_active', true), $talent)->get(),
        );
        $languages = $this->matchTranslatedValues(
            $rawLanguages,
            Language::query()->where('is_active', true)->with('translations')->get(),
        );

        return [
            'suggestions' => [
                'personal_information' => [
                    'full_name' => $this->string($parsed, ['full_name', 'candidate_name', 'name']),
                    'email' => $this->string($parsed, ['email', 'candidate_email']),
                    'phone_country_code' => $this->string($parsed, ['phone_country_code']),
                    'phone_number' => $this->string($parsed, ['phone_number', 'candidate_phone', 'phone']),
                    'residence_country_id' => $country?->getKey(),
                    'residence_city_id' => $city?->getKey(),
                    'languages' => array_values(array_filter(array_map(fn (array $language): ?array => $language['match'] === null ? null : [
                        'language_id' => $language['match']['id'],
                        'proficiency_level' => null,
                    ], $languages))),
                ],
                'skills_and_experiences' => [
                    'professional_summary' => $this->string($parsed, [
                        'professional_summary',
                        'candidate_summary_objective',
                        'brief_summary',
                        'summary',
                    ]),
                    'years_of_experience' => $this->integer($parsed, ['years_of_experience']),
                    'job_title_id' => $jobTitle?->getKey(),
                    'seniority_level' => null,
                    'linkedin_url' => $this->string($parsed, ['linkedin_url', 'candidate_linkedin', 'linkedin']),
                    'github_url' => $this->string($parsed, ['github_url', 'candidate_github', 'github']),
                    'behance_url' => $this->string($parsed, ['behance_url', 'candidate_behance', 'behance']),
                    'portfolio_url' => $this->string($parsed, ['portfolio_url', 'candidate_website', 'website']),
                    'skill_ids' => $this->matchedIds($skills),
                    'tool_ids' => $this->matchedIds($tools),
                    'experiences' => $this->experiences($positionItems),
                    'projects' => $this->projects($projectItems),
                ],
            ],
        ];
    }

    public function hasUsefulData(array $mapped): bool
    {
        $values = Arr::flatten($mapped['suggestions'] ?? []);

        return collect($values)->contains(fn (mixed $value): bool => filled($value));
    }

    /** @return array<int, array<string, mixed>> */
    private function experiences(array $positions): array
    {
        return collect($positions)
            ->filter(fn (mixed $position): bool => is_array($position))
            ->map(function (array $position, int $index): array {
                $endedAt = $this->string($position, ['end_date', 'ended_at']);

                return [
                    'job_title' => $this->string($position, ['position_name', 'job_title', 'title']),
                    'company_name' => $this->string($position, ['company_name', 'company']),
                    'company_url' => $this->string($position, ['company_url']),
                    'started_at' => $this->date($position, ['start_date', 'started_at']),
                    'ended_at' => $this->date($position, ['end_date', 'ended_at']),
                    'currently_working' => $endedAt === null,
                    'responsibilities' => $this->string($position, ['job_details', 'responsibilities', 'description']),
                    'display_order' => $index,
                ];
            })
            ->filter(fn (array $position): bool => filled($position['job_title']) || filled($position['company_name']))
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function projects(array $projects): array
    {
        return collect($projects)
            ->filter(fn (mixed $project): bool => is_array($project))
            ->map(fn (array $project, int $index): array => [
                'name' => $this->string($project, ['project_name', 'name', 'title']),
                'description' => $this->string($project, ['description', 'project_details', 'details']),
                'url' => $this->string($project, ['url', 'project_url']),
                'display_order' => $index,
            ])
            ->filter(fn (array $project): bool => filled($project['name']))
            ->values()
            ->all();
    }

    private function scopedQuery(Builder $query, Talent $talent): Builder
    {
        if ($talent->professional_field_id === null) {
            return $query;
        }

        return $query->whereHas('professionalFields', fn (Builder $fieldQuery) => $fieldQuery
            ->where('professional_fields.id', $talent->professional_field_id));
    }

    /** @return array<int, array{raw: string, match: array{id: int, name: string}|null}> */
    private function matchNamedValues(array $values, Collection $models): array
    {
        return array_map(fn (string $value): array => $this->matchedValue(
            $value,
            $this->matchNamedModel($models, $value),
        ), $values);
    }

    /** @return array<int, array{raw: string, match: array{id: int, name: string}|null}> */
    private function matchTranslatedValues(array $values, Collection $models): array
    {
        return array_map(fn (string $value): array => $this->matchedValue(
            $value,
            $this->matchTranslatedModel($models, $value),
        ), $values);
    }

    private function matchNamedModel(Collection $models, ?string $value): ?Model
    {
        if ($value === null) {
            return null;
        }

        $normalized = $this->normalize($value);

        return $models->first(fn (Model $model): bool => $this->normalize((string) $model->getAttribute('name')) === $normalized);
    }

    private function matchTranslatedModel(Collection $models, ?string $value): ?Model
    {
        if ($value === null) {
            return null;
        }

        $normalized = $this->normalize($value);

        return $models->first(fn (Model $model): bool => $model->translations
            ->contains(fn (Model $translation): bool => $this->normalize((string) $translation->getAttribute('name')) === $normalized));
    }

    /** @return array{raw: string|null, match: array{id: int, name: string}|null} */
    private function matchedValue(?string $raw, ?Model $model): array
    {
        return [
            'raw' => $raw,
            'match' => $model === null ? null : [
                'id' => (int) $model->getKey(),
                'name' => (string) $model->getAttribute('name'),
            ],
        ];
    }

    /** @return array<int, int> */
    private function matchedIds(array $values): array
    {
        return collect($values)
            ->pluck('match.id')
            ->filter(fn (mixed $id): bool => $id !== null)
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));

        return preg_replace('/[\s\p{P}\p{S}]+/u', ' ', $value) ?? $value;
    }

    /** @return array<int, mixed> */
    private function array(array $data, array $keys): array
    {
        foreach ($keys as $key) {
            $value = Arr::get($data, $key);
            if (is_array($value)) {
                return array_is_list($value) ? $value : [$value];
            }
        }

        return [];
    }

    private function string(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = Arr::get($data, $key);
            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return null;
    }

    private function integer(array $data, array $keys): ?int
    {
        $value = $this->string($data, $keys);

        return $value !== null && is_numeric($value) ? (int) round((float) $value) : null;
    }

    private function date(array $data, array $keys): ?string
    {
        $value = $this->string($data, $keys);

        return $value !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 ? $value : null;
    }

    /** @return array<int, string> */
    private function uniqueStrings(array $values): array
    {
        return collect($values)
            ->filter(fn (mixed $value): bool => is_scalar($value) && trim((string) $value) !== '')
            ->map(fn (mixed $value): string => trim((string) $value))
            ->unique(fn (string $value): string => $this->normalize($value))
            ->values()
            ->all();
    }
}
