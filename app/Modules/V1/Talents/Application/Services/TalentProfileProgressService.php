<?php

namespace App\Modules\V1\Talents\Application\Services;

use App\Modules\V1\ProfessionalFields\Domain\ValueObjects\ProfessionalFormFieldEnum;
use App\Modules\V1\TalentApplications\Application\Contracts\ApplicationFormFieldPolicyProviderInterface;
use App\Modules\V1\Talents\Domain\Models\Talent;
use App\Modules\V1\Talents\Domain\ValueObjects\OnboardingStepEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\OnboardingStepStatusEnum;
use BackedEnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class TalentProfileProgressService
{
    public function __construct(
        private readonly ApplicationFormFieldPolicyProviderInterface $fieldPolicy,
    ) {}

    public function calculate(
        Talent $talent,
        ?array $formFieldConfiguration = null,
    ): array {
        $allData = $this->dataByStep($talent);
        $configuration = $formFieldConfiguration
            ?? $this->fieldPolicy->all($talent->professional_field_id);

        $steps = [];
        foreach (OnboardingStepEnum::cases() as $step) {
            $stepConfiguration = $configuration[$step->value];
            $visibleFields = $stepConfiguration->visibleFields;
            $requiredFields = $this->requiredFields(
                $talent,
                $visibleFields,
                $stepConfiguration->requiredFields,
            );
            $data = $this->visibleData(
                $allData[$step->value],
                $visibleFields,
            );
            $missingFields = $this->missingFields(
                $talent,
                $requiredFields,
            );
            $hasStepData = $this->hasData($data);
            $isComplete = $step->isRequired()
                ? $missingFields === []
                : $hasStepData;
            $status = match (true) {
                $isComplete => OnboardingStepStatusEnum::COMPLETED,
                $hasStepData => OnboardingStepStatusEnum::IN_PROGRESS,
                default => OnboardingStepStatusEnum::NOT_STARTED,
            };

            $steps[$step->value] = [
                'key' => $step->value,
                'route_name' => $step->value,
                'required' => $step->isRequired(),
                'status' => $status->value,
                'visible_fields' => $visibleFields,
                'required_fields' => $requiredFields,
                'missing_fields' => $missingFields,
                'data' => $data,
            ];
        }

        $requiredComplete = collect($steps)
            ->filter(fn (array $step) => $step['required'])
            ->every(
                fn (array $step) => $step['status']
                    === OnboardingStepStatusEnum::COMPLETED->value,
            );
        $completedCount = collect($steps)
            ->where('status', OnboardingStepStatusEnum::COMPLETED->value)
            ->count();
        $stepCount = count(OnboardingStepEnum::cases());
        $current = collect(OnboardingStepEnum::cases())
            ->first(
                fn (OnboardingStepEnum $step) => $step->isRequired()
                    && $steps[$step->value]['status']
                        !== OnboardingStepStatusEnum::COMPLETED->value
            );

        return [
            'steps' => $steps,
            'current_step' => $current?->value,
            'required_steps_completed' => $requiredComplete,
            'completion_percentage' => (int) round(($completedCount / $stepCount) * 100),
        ];
    }

    private function dataByStep(Talent $talent): array
    {
        return [
            OnboardingStepEnum::PERSONAL_INFORMATION->value => [
                'professional_field' => $this->lookup($talent->professionalField),
                'full_name' => trim(
                    $talent->user->first_name.' '.$talent->user->last_name,
                ),
                'email' => $talent->user->email,
                'phone_country_code' => $talent->phone_country_code,
                'phone_number' => $talent->phone_number,
                'whatsapp_country_code' => $talent->whatsapp_country_code,
                'whatsapp_number' => $talent->whatsapp_number,
                'date_of_birth' => $talent->date_of_birth?->toDateString(),
                'gender' => $this->value($talent->gender),
                'religion' => $this->value($talent->religion),
                'nationality_country' => $this->lookup($talent->nationalityCountry),
                'residence_country' => $this->lookup($talent->residenceCountry),
                'residence_city' => $this->lookup($talent->residenceCity),
                'languages' => $talent->languages
                    ->map(fn ($language) => [
                        'id' => $language->id,
                        'name' => $this->localizedName($language),
                        'proficiency_level' => $language->pivot->proficiency_level,
                    ])
                    ->values()
                    ->all(),
                'cv' => $this->media($talent->getFirstMedia('cv')),
            ],
            OnboardingStepEnum::SKILLS_AND_EXPERIENCES->value => [
                'professional_summary' => $talent->professional_summary,
                'job_title' => $this->lookup($talent->jobTitle),
                'seniority_level' => $this->value($talent->seniority_level),
                'years_of_experience' => $talent->years_of_experience,
                'skills' => $talent->skills
                    ->map(fn ($skill) => [
                        'id' => $skill->id,
                        'name' => $skill->name,
                    ])
                    ->values()
                    ->all(),
                'tools' => $talent->tools
                    ->map(fn ($tool) => [
                        'id' => $tool->id,
                        'name' => $tool->name,
                    ])
                    ->values()
                    ->all(),
                'experiences' => $talent->experiences
                    ->map(fn ($experience) => [
                        'id' => $experience->id,
                        'job_title' => $experience->job_title,
                        'company_name' => $experience->company_name,
                        'company_url' => $experience->company_url,
                        'started_at' => $experience->started_at?->toDateString(),
                        'ended_at' => $experience->ended_at?->toDateString(),
                        'currently_working' => $experience->currently_working,
                        'responsibilities' => $experience->responsibilities,
                        'display_order' => $experience->display_order,
                    ])
                    ->values()
                    ->all(),
                'linkedin_url' => $talent->linkedin_url,
                'github_url' => $talent->github_url,
                'behance_url' => $talent->behance_url,
                'portfolio_url' => $talent->portfolio_url,
                'projects' => $talent->projects
                    ->map(fn ($project) => [
                        'id' => $project->id,
                        'name' => $project->name,
                        'description' => $project->description,
                        'url' => $project->url,
                        'display_order' => $project->display_order,
                        'images' => $project
                            ->getMedia('images')
                            ->map(fn (Media $image) => $this->media($image))
                            ->values()
                            ->all(),
                    ])
                    ->values()
                    ->all(),
            ],
            OnboardingStepEnum::WORK_PREFERENCES->value => [
                'work_types' => $talent->workTypes
                    ->pluck('work_type')
                    ->map(fn ($value) => $this->value($value))
                    ->values()
                    ->all(),
                'work_modes' => $talent->workModes
                    ->pluck('work_mode')
                    ->map(fn ($value) => $this->value($value))
                    ->values()
                    ->all(),
                'willing_to_relocate' => $talent->willing_to_relocate,
                'notice_period_days' => $talent->notice_period_days,
                'current_salary' => $talent->current_salary,
                'current_currency' => $this->lookup($talent->currentCurrency),
                'expected_salary_min' => $talent->expected_salary_min,
                'expected_salary_max' => $talent->expected_salary_max,
                'expected_currency' => $this->lookup($talent->expectedCurrency),
            ],
        ];
    }

    private function requiredFields(
        Talent $talent,
        array $visibleFields,
        array $requiredFields,
    ): array {
        $experiencesAreRequired = ($talent->years_of_experience ?? 0) > 0
            && in_array(
                ProfessionalFormFieldEnum::EXPERIENCES->value,
                $visibleFields,
                true,
            );

        if ($experiencesAreRequired) {
            $requiredFields[] = ProfessionalFormFieldEnum::EXPERIENCES->value;
        }

        return array_values(array_unique($requiredFields));
    }

    private function missingFields(Talent $talent, array $requiredFields): array
    {
        return collect($requiredFields)
            ->filter(function (string $fieldKey) use ($talent): bool {
                $field = ProfessionalFormFieldEnum::tryFrom($fieldKey);

                return $field !== null && $this->isMissing($talent, $field);
            })
            ->values()
            ->all();
    }

    private function isMissing(
        Talent $talent,
        ProfessionalFormFieldEnum $field,
    ): bool {
        return match ($field) {
            ProfessionalFormFieldEnum::PROFESSIONAL_FIELD => $talent->professional_field_id === null,
            ProfessionalFormFieldEnum::FULL_NAME => blank($talent->user->first_name)
                || blank($talent->user->last_name),
            ProfessionalFormFieldEnum::EMAIL => blank($talent->user->email),
            ProfessionalFormFieldEnum::PROFESSIONAL_SUMMARY => blank($talent->professional_summary),
            ProfessionalFormFieldEnum::PHONE_NUMBER => blank($talent->phone_country_code)
                || blank($talent->phone_number),
            ProfessionalFormFieldEnum::WHATSAPP_NUMBER => blank($talent->whatsapp_country_code)
                || blank($talent->whatsapp_number),
            ProfessionalFormFieldEnum::DATE_OF_BIRTH => $talent->date_of_birth === null,
            ProfessionalFormFieldEnum::GENDER => $talent->gender === null,
            ProfessionalFormFieldEnum::RELIGION => $talent->religion === null,
            ProfessionalFormFieldEnum::NATIONALITY_COUNTRY => $talent->nationality_country_id === null,
            ProfessionalFormFieldEnum::RESIDENCE_COUNTRY => $talent->residence_country_id === null,
            ProfessionalFormFieldEnum::RESIDENCE_CITY => $talent->residence_city_id === null,
            ProfessionalFormFieldEnum::LANGUAGES => $talent->languages->isEmpty(),
            ProfessionalFormFieldEnum::JOB_TITLE => $talent->job_title_id === null
                || ! DB::table('job_title_professional_field')
                    ->where('professional_field_id', $talent->professional_field_id)
                    ->where('job_title_id', $talent->job_title_id)
                    ->exists(),
            ProfessionalFormFieldEnum::SENIORITY_LEVEL => $talent->seniority_level === null,
            ProfessionalFormFieldEnum::YEARS_OF_EXPERIENCE => $talent->years_of_experience === null,
            ProfessionalFormFieldEnum::SKILLS => $talent->skills->isEmpty()
                || $this->hasSelectionsOutsideProfessionalField(
                    pivotTable: 'professional_field_skill',
                    relatedColumn: 'skill_id',
                    selectedIds: $talent->skills->pluck('id')->all(),
                    professionalFieldId: $talent->professional_field_id,
                ),
            ProfessionalFormFieldEnum::TOOLS => $talent->tools->isEmpty()
                || $this->hasSelectionsOutsideProfessionalField(
                    pivotTable: 'professional_field_tool',
                    relatedColumn: 'tool_id',
                    selectedIds: $talent->tools->pluck('id')->all(),
                    professionalFieldId: $talent->professional_field_id,
                ),
            ProfessionalFormFieldEnum::LINKEDIN_URL => blank($talent->linkedin_url),
            ProfessionalFormFieldEnum::GITHUB_URL => blank($talent->github_url),
            ProfessionalFormFieldEnum::BEHANCE_URL => blank($talent->behance_url),
            ProfessionalFormFieldEnum::PORTFOLIO_URL => blank($talent->portfolio_url),
            ProfessionalFormFieldEnum::EXPERIENCES => $talent->experiences->isEmpty(),
            ProfessionalFormFieldEnum::PROJECTS => $talent->projects->isEmpty(),
            ProfessionalFormFieldEnum::WORK_TYPES => $talent->workTypes->isEmpty(),
            ProfessionalFormFieldEnum::WORK_MODES => $talent->workModes->isEmpty(),
            ProfessionalFormFieldEnum::WILLING_TO_RELOCATE => $talent->willing_to_relocate === null,
            ProfessionalFormFieldEnum::NOTICE_PERIOD => $talent->notice_period_days === null,
            ProfessionalFormFieldEnum::CURRENT_SALARY => $talent->current_salary === null
                || $talent->current_currency_id === null,
            ProfessionalFormFieldEnum::EXPECTED_SALARY => $talent->expected_salary_min === null
                || $talent->expected_salary_max === null
                || $talent->expected_currency_id === null,
            ProfessionalFormFieldEnum::CV => $talent->getFirstMedia('cv') === null,
        };
    }

    private function hasSelectionsOutsideProfessionalField(
        string $pivotTable,
        string $relatedColumn,
        array $selectedIds,
        ?int $professionalFieldId,
    ): bool {
        if ($professionalFieldId === null || $selectedIds === []) {
            return true;
        }

        $linkedCount = DB::table($pivotTable)
            ->where('professional_field_id', $professionalFieldId)
            ->whereIn($relatedColumn, $selectedIds)
            ->count();

        return $linkedCount !== count($selectedIds);
    }

    private function visibleData(array $data, array $visibleFields): array
    {
        $responseKeys = collect($visibleFields)
            ->map(fn (string $fieldKey): ?ProfessionalFormFieldEnum => ProfessionalFormFieldEnum::tryFrom($fieldKey))
            ->filter()
            ->flatMap(
                fn (ProfessionalFormFieldEnum $field): array => $field->responseKeys(),
            )
            ->unique()
            ->all();

        return Arr::only($data, $responseKeys);
    }

    private function hasData(array $data): bool
    {
        foreach ($data as $value) {
            if (is_array($value)) {
                if ($this->hasData($value)) {
                    return true;
                }

                continue;
            }

            if ($value !== null && $value !== '') {
                return true;
            }
        }

        return false;
    }

    private function lookup(mixed $model): ?array
    {
        if ($model === null) {
            return null;
        }

        $data = [
            'id' => $model->id,
            'name' => $this->localizedName($model),
        ];

        if (array_key_exists('code', $model->getAttributes())) {
            $data['code'] = $model->code;
        }

        return $data;
    }

    private function localizedName(mixed $model): ?string
    {
        return $model->getRawOriginal('localized_name')
            ?? $model->name;
    }

    private function value(mixed $value): mixed
    {
        return $value instanceof BackedEnum ? $value->value : $value;
    }

    private function media(?Media $media): ?array
    {
        if ($media === null) {
            return null;
        }

        return [
            'id' => $media->id,
            'name' => $media->name,
            'file_name' => $media->file_name,
            'mime_type' => $media->mime_type,
            'size' => $media->size,
            'download_url' => route('talent.profile.media.download', ['media' => $media->id]),
        ];
    }
}
