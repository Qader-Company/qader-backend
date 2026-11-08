<?php

namespace App\Modules\V1\Talents\Application\Services;

use App\Modules\V1\Cities\Domain\Models\City;
use App\Modules\V1\ProfessionalFields\Domain\ValueObjects\ProfessionalFormFieldEnum;
use App\Modules\V1\TalentApplications\Application\Contracts\ApplicationFormFieldPolicyProviderInterface;
use App\Modules\V1\Talents\Domain\Models\Talent;
use App\Modules\V1\Talents\Domain\Models\TalentProject;
use App\Modules\V1\Talents\Domain\Repositories\TalentRepositoryInterface;
use App\Modules\V1\Talents\Domain\ValueObjects\OnboardingStepEnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TalentProfileWriter
{
    public function __construct(
        private readonly TalentRepositoryInterface $talents,
        private readonly ApplicationFormFieldPolicyProviderInterface $fieldPolicy,
    ) {}

    public function save(Talent $talent, array $sections): Talent
    {
        return DB::transaction(function () use ($talent, $sections) {
            foreach (OnboardingStepEnum::cases() as $step) {
                if (! array_key_exists($step->value, $sections)) {
                    continue;
                }

                $data = $sections[$step->value];

                match ($step) {
                    OnboardingStepEnum::PERSONAL_INFORMATION => $this
                        ->savePersonalInformation($talent, $data),
                    OnboardingStepEnum::SKILLS_AND_EXPERIENCES => $this
                        ->saveProfessionalInformation($talent, $data),
                    OnboardingStepEnum::WORK_PREFERENCES => $this
                        ->saveWorkPreferences($talent, $data),
                };
            }

            return $this->fresh($talent);
        });
    }

    private function savePersonalInformation(Talent $talent, array $data): void
    {
        $this->updateUserInformation($talent, $data);

        $attributes = Arr::only($data, [
            'professional_field_id',
            'phone_country_code',
            'phone_number',
            'whatsapp_country_code',
            'whatsapp_number',
            'date_of_birth',
            'gender',
            'religion',
            'nationality_country_id',
            'residence_country_id',
            'residence_city_id',
        ]);

        $residenceCountryId = $attributes['residence_country_id'] ?? $talent->residence_country_id;
        $residenceCityId = $attributes['residence_city_id'] ?? $talent->residence_city_id;
        $isValidResidenceCity = $residenceCityId === null
            || City::query()
                ->whereKey($residenceCityId)
                ->where('country_id', $residenceCountryId)
                ->where('is_active', true)
                ->exists();

        if (! $isValidResidenceCity) {
            throw ValidationException::withMessages([
                'residence_city_id' => [
                    'The selected city must belong to the residence country.',
                ],
            ]);
        }

        $talent->update($attributes);

        if (array_key_exists('languages', $data)) {
            $languages = collect($data['languages'])
                ->mapWithKeys(fn (array $language) => [
                    $language['language_id'] => [
                        'proficiency_level' => $language['proficiency_level'],
                    ],
                ])
                ->all();

            $talent->languages()->sync($languages);
        }

        if (isset($data['cv'])) {
            $talent->addMedia($data['cv'])->toMediaCollection('cv', 'local');
        }
    }

    private function updateUserInformation(Talent $talent, array $data): void
    {
        $attributes = [];

        if (array_key_exists('full_name', $data)) {
            [$firstName, $lastName] = $this->splitFullName($data['full_name']);

            $attributes['first_name'] = $firstName;
            $attributes['last_name'] = $lastName;
        }

        if ($attributes === []) {
            return;
        }

        $talent->user()->update($attributes);
    }

    private function splitFullName(string $fullName): array
    {
        $nameParts = preg_split('/\s+/', trim($fullName), 2);

        return [
            $nameParts[0],
            $nameParts[1] ?? '',
        ];
    }

    private function saveProfessionalInformation(Talent $talent, array $data): void
    {
        $attributes = Arr::only($data, [
            'professional_summary',
            'job_title_id',
            'seniority_level',
            'years_of_experience',
        ]);
        $talent->update($attributes);

        if (array_key_exists('skill_ids', $data)) {
            $talent->skills()->sync($data['skill_ids']);
        }

        if (array_key_exists('industry_ids', $data)) {
            $talent->industries()->sync($data['industry_ids']);
        }

        if (array_key_exists('tool_ids', $data)) {
            $talent->tools()->sync($data['tool_ids']);
        }

        if (array_key_exists('experiences', $data)) {
            $this->replaceChildren($talent, 'experiences', $data['experiences'], [
                'job_title',
                'company_name',
                'company_url',
                'started_at',
                'ended_at',
                'currently_working',
                'responsibilities',
                'display_order',
            ]);
        }

        $this->saveProfessionalLinksAndProjects($talent, $data);
        $this->validateProfessionalFieldSelections($talent);
    }

    private function saveWorkPreferences(Talent $talent, array $data): void
    {
        $talent->update(Arr::only($data, [
            'willing_to_relocate',
            'notice_period_days',
            'current_salary',
            'current_currency_id',
            'expected_salary_min',
            'expected_salary_max',
            'expected_currency_id',
        ]));
        if (array_key_exists('work_types', $data)) {
            $this->replaceValues($talent, 'workTypes', 'work_type', $data['work_types']);
        }
        if (array_key_exists('work_modes', $data)) {
            $this->replaceValues($talent, 'workModes', 'work_mode', $data['work_modes']);
        }

        $talent->refresh();
        $hasInvalidSalaryRange = $talent->expected_salary_min !== null
            && $talent->expected_salary_max !== null
            && (float) $talent->expected_salary_min > (float) $talent->expected_salary_max;

        if ($hasInvalidSalaryRange) {
            throw ValidationException::withMessages([
                'expected_salary_max' => [
                    'The maximum expected salary must be greater than or equal to the minimum.',
                ],
            ]);
        }
    }

    private function saveProfessionalLinksAndProjects(Talent $talent, array $data): void
    {
        $attributes = Arr::only($data, [
            'linkedin_url',
            'github_url',
            'behance_url',
            'portfolio_url',
        ]);
        $talent->update($attributes);

        if (array_key_exists('projects', $data)) {
            $this->replaceProjects($talent, $data['projects']);
        }
    }

    private function validateProfessionalFieldSelections(Talent $talent): void
    {
        $talent->refresh();
        $professionalFieldId = $talent->professional_field_id;

        if ($professionalFieldId === null) {
            return;
        }

        $fieldPolicy = $this->fieldPolicy->forStep(
            $professionalFieldId,
            OnboardingStepEnum::SKILLS_AND_EXPERIENCES,
        );
        $visibleFields = $fieldPolicy->visibleFields;

        $hasInvalidJobTitle = $talent->job_title_id !== null
            && ! DB::table('job_title_professional_field')
                ->where('job_title_id', $talent->job_title_id)
                ->where('professional_field_id', $professionalFieldId)
                ->exists();

        if ($hasInvalidJobTitle) {
            throw ValidationException::withMessages([
                'job_title_id' => [
                    'The selected job title does not belong to the professional field.',
                ],
            ]);
        }

        $skillIds = DB::table('talent_skills')
            ->where('talent_id', $talent->id)
            ->pluck('skill_id');
        $linkedSkillCount = DB::table('professional_field_skill')
            ->where('professional_field_id', $professionalFieldId)
            ->whereIn('skill_id', $skillIds)
            ->count();

        if ($linkedSkillCount !== $skillIds->count()) {
            throw ValidationException::withMessages([
                'skill_ids' => [
                    'All selected skills must belong to the professional field.',
                ],
            ]);
        }

        if (in_array(ProfessionalFormFieldEnum::TOOLS->value, $visibleFields, true)) {
            $toolIds = DB::table('talent_tools')
                ->where('talent_id', $talent->id)
                ->pluck('tool_id');
            $linkedToolCount = DB::table('professional_field_tool')
                ->where('professional_field_id', $professionalFieldId)
                ->whereIn('tool_id', $toolIds)
                ->count();

            if ($linkedToolCount !== $toolIds->count()) {
                throw ValidationException::withMessages([
                    'tool_ids' => [
                        'All selected tools must belong to the professional field.',
                    ],
                ]);
            }
        }
    }

    private function replaceProjects(Talent $talent, array $items): void
    {
        $ids = collect($items)
            ->pluck('id')
            ->filter()
            ->values();
        $ownedProjectCount = $talent
            ->projects()
            ->whereIn('id', $ids)
            ->count();

        if ($ownedProjectCount !== $ids->count()) {
            throw ValidationException::withMessages([
                'projects.id' => [
                    'One or more selected projects do not belong to this profile.',
                ],
            ]);
        }

        $persistedIds = collect();

        foreach ($items as $item) {
            $attributes = Arr::only($item, [
                'name',
                'description',
                'url',
                'display_order',
            ]);

            if (isset($item['id'])) {
                $project = $talent->projects()->find($item['id']);

                if ($project === null) {
                    throw ValidationException::withMessages([
                        'projects.id' => [
                            'The selected project does not belong to this profile.',
                        ],
                    ]);
                }

                $project->update($attributes);
            } else {
                $project = $talent->projects()->create($attributes);
            }

            $this->syncProjectImages($project, $item);
            $persistedIds->push($project->id);
        }

        $projectsToDelete = $talent->projects();

        if ($persistedIds->isNotEmpty()) {
            $projectsToDelete->whereNotIn('id', $persistedIds);
        }

        $projectsToDelete
            ->get()
            ->each(fn (TalentProject $project) => $project->delete());
    }

    private function syncProjectImages(TalentProject $project, array $item): void
    {
        $removedImageIds = $item['removed_image_ids'] ?? [];
        $imagesToDelete = $project
            ->media()
            ->where('collection_name', 'images')
            ->whereIn('id', $removedImageIds)
            ->get();

        if ($imagesToDelete->count() !== count($removedImageIds)) {
            throw ValidationException::withMessages([
                'projects.removed_image_ids' => [
                    'One or more selected images do not belong to this project.',
                ],
            ]);
        }

        $newImages = $item['images'] ?? [];
        $currentImageCount = $project
            ->media()
            ->where('collection_name', 'images')
            ->count();
        $finalImageCount = $currentImageCount
            - $imagesToDelete->count()
            + count($newImages);

        if ($finalImageCount > 5) {
            throw ValidationException::withMessages([
                'projects.images' => [
                    'A project may have no more than five images.',
                ],
            ]);
        }

        $imagesToDelete->each(fn ($image) => $image->delete());

        foreach ($newImages as $image) {
            $project
                ->addMedia($image)
                ->toMediaCollection('images', 'local');
        }
    }

    private function replaceValues(Talent $talent, string $relation, string $column, array $values): void
    {
        $talent->{$relation}()->delete();
        $talent->{$relation}()->createMany(array_map(fn ($value) => [$column => $value], $values));
    }

    private function replaceChildren(Talent $talent, string $relation, array $items, array $allowed): void
    {
        $ids = collect($items)->pluck('id')->filter()->values();
        $talent->{$relation}()->whereNotIn('id', $ids)->delete();

        foreach ($items as $item) {
            $attributes = Arr::only($item, $allowed);
            if (isset($item['id'])) {
                $record = $talent->{$relation}()->find($item['id']);
                if ($record === null) {
                    throw ValidationException::withMessages([
                        "{$relation}.id" => [
                            'The selected record does not belong to this profile.',
                        ],
                    ]);
                }
                $record->update($attributes);
            } else {
                $talent->{$relation}()->create($attributes);
            }
        }
    }

    private function fresh(Talent $talent): Talent
    {
        return $this->talents->findProfileByUserId($talent->user_id)
            ?? throw new \LogicException('Talent profile was not found after update.');
    }
}
