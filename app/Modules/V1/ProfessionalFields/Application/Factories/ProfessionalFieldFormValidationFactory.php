<?php

namespace App\Modules\V1\ProfessionalFields\Application\Factories;

use App\Modules\V1\ProfessionalFields\Domain\ValueObjects\ProfessionalFormFieldEnum;
use App\Modules\V1\TalentApplications\Application\Contracts\ApplicationFormFieldPolicyProviderInterface;
use App\Modules\V1\TalentApplications\Application\Contracts\ApplicationFormValidationFactoryInterface;
use App\Modules\V1\Talents\Domain\ValueObjects\GenderEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\LanguageProficiencyEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\OnboardingStepEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\ReligionEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\SeniorityLevelEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\WorkModeEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\WorkTypeEnum;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Validation\Rule;

class ProfessionalFieldFormValidationFactory implements ApplicationFormValidationFactoryInterface
{
    public function __construct(
        private readonly ApplicationFormFieldPolicyProviderInterface $fieldPolicy,
    ) {}

    public function rulesFor(
        OnboardingStepEnum $step,
        ?int $professionalFieldId,
        ?int $userId = null,
        string $prefix = '',
        bool $enforceRequiredFields = true,
        array $context = [],
    ): array {
        $rules = match ($step) {
            OnboardingStepEnum::PERSONAL_INFORMATION => $this
                ->personalInformation($prefix, $userId),
            OnboardingStepEnum::SKILLS_AND_EXPERIENCES => $this
                ->skillsAndExperiences($prefix),
            OnboardingStepEnum::WORK_PREFERENCES => $this
                ->workPreferences($prefix),
        };
        $policy = $this->fieldPolicy->forStep(
            $professionalFieldId,
            $step,
        );
        $requiredFields = $this->requiredFields(
            $step,
            $policy->visibleFields,
            $policy->requiredFields,
            $context,
        );

        return $this->applyFieldPolicy(
            rules: $rules,
            step: $step,
            prefix: $prefix,
            visibleFields: $policy->visibleFields,
            requiredFields: $requiredFields,
            enforceRequiredFields: $enforceRequiredFields,
        );
    }

    private function personalInformation(
        string $prefix,
        ?int $userId,
    ): array {
        return [
            $prefix.'professional_field_id' => [
                'sometimes',
                'nullable',
                Rule::exists('professional_fields', 'id')
                    ->where('is_active', true),
            ],
            $prefix.'full_name' => [
                'sometimes',
                'required',
                'string',
                'max:200',
            ],
            $prefix.'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->where('type', PortalTypeEnum::TALENT->value)
                    ->ignore($userId),
            ],
            $prefix.'professional_summary' => [
                'sometimes',
                'nullable',
                'string',
                'max:5000',
            ],
            $prefix.'phone_country_code' => [
                'sometimes',
                'nullable',
                'string',
                'max:5',
            ],
            $prefix.'phone_number' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
            ],
            $prefix.'whatsapp_country_code' => [
                'sometimes',
                'nullable',
                'string',
                'max:5',
            ],
            $prefix.'whatsapp_number' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
            ],
            $prefix.'date_of_birth' => [
                'sometimes',
                'nullable',
                'date',
                'before:today',
            ],
            $prefix.'gender' => [
                'sometimes',
                'nullable',
                Rule::enum(GenderEnum::class),
            ],
            $prefix.'religion' => [
                'sometimes',
                'nullable',
                Rule::enum(ReligionEnum::class),
            ],
            $prefix.'nationality_country_id' => [
                'sometimes',
                'nullable',
                Rule::exists('countries', 'id')->where('is_active', true),
            ],
            $prefix.'residence_country_id' => [
                'sometimes',
                'nullable',
                Rule::exists('countries', 'id')->where('is_active', true),
            ],
            $prefix.'residence_city_id' => [
                'sometimes',
                'nullable',
                Rule::exists('cities', 'id')->where('is_active', true),
            ],
            $prefix.'languages' => ['sometimes', 'array'],
            $prefix.'languages.*.language_id' => [
                'required',
                Rule::exists('languages', 'id')->where('is_active', true),
                'distinct',
            ],
            $prefix.'languages.*.proficiency_level' => [
                'required',
                Rule::enum(LanguageProficiencyEnum::class),
            ],
        ];
    }

    private function skillsAndExperiences(string $prefix): array
    {
        return [
            $prefix.'job_title_id' => [
                'sometimes',
                'nullable',
                Rule::exists('job_titles', 'id')->where('is_active', true),
            ],
            $prefix.'seniority_level' => [
                'sometimes',
                'nullable',
                Rule::enum(SeniorityLevelEnum::class),
            ],
            $prefix.'years_of_experience' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0',
                'max:80',
            ],
            $prefix.'skill_ids' => ['sometimes', 'array'],
            $prefix.'skill_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('skills', 'id')->where('is_active', true),
            ],
            $prefix.'tool_ids' => ['sometimes', 'array'],
            $prefix.'tool_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('tools', 'id')->where('is_active', true),
            ],
            $prefix.'linkedin_url' => [
                'sometimes',
                'nullable',
                'url',
                'max:255',
            ],
            $prefix.'github_url' => [
                'sometimes',
                'nullable',
                'url',
                'max:255',
            ],
            $prefix.'behance_url' => [
                'sometimes',
                'nullable',
                'url',
                'max:255',
            ],
            $prefix.'portfolio_url' => [
                'sometimes',
                'nullable',
                'url',
                'max:255',
            ],
            $prefix.'experiences' => ['sometimes', 'array'],
            $prefix.'experiences.*.id' => ['sometimes', 'integer'],
            $prefix.'experiences.*.job_title' => [
                'required',
                'string',
                'max:255',
            ],
            $prefix.'experiences.*.company_name' => [
                'required',
                'string',
                'max:255',
            ],
            $prefix.'experiences.*.company_url' => [
                'nullable',
                'url',
                'max:255',
            ],
            $prefix.'experiences.*.started_at' => ['required', 'date'],
            $prefix.'experiences.*.ended_at' => [
                'nullable',
                'date',
                'after_or_equal:'.$prefix.'experiences.*.started_at',
            ],
            $prefix.'experiences.*.currently_working' => [
                'required',
                'boolean',
            ],
            $prefix.'experiences.*.responsibilities' => [
                'nullable',
                'string',
                'max:5000',
            ],
            $prefix.'experiences.*.display_order' => [
                'nullable',
                'integer',
                'min:0',
            ],
            $prefix.'projects' => ['sometimes', 'array'],
            $prefix.'projects.*.id' => ['sometimes', 'integer'],
            $prefix.'projects.*.name' => [
                'required',
                'string',
                'max:255',
            ],
            $prefix.'projects.*.description' => [
                'nullable',
                'string',
                'max:5000',
            ],
            $prefix.'projects.*.url' => [
                'nullable',
                'url',
                'max:255',
            ],
            $prefix.'projects.*.display_order' => [
                'nullable',
                'integer',
                'min:0',
            ],
            $prefix.'projects.*.images' => [
                'sometimes',
                'array',
                'max:5',
            ],
            $prefix.'projects.*.images.*' => [
                'file',
                'mimes:jpg,jpeg,png',
                'max:5120',
            ],
            $prefix.'projects.*.removed_image_ids' => [
                'sometimes',
                'array',
            ],
            $prefix.'projects.*.removed_image_ids.*' => [
                'integer',
                'distinct',
            ],
        ];
    }

    private function workPreferences(string $prefix): array
    {
        return [
            $prefix.'work_types' => ['sometimes', 'array'],
            $prefix.'work_types.*' => [
                'required',
                'distinct',
                Rule::enum(WorkTypeEnum::class),
            ],
            $prefix.'work_modes' => ['sometimes', 'array'],
            $prefix.'work_modes.*' => [
                'required',
                'distinct',
                Rule::enum(WorkModeEnum::class),
            ],
            $prefix.'willing_to_relocate' => [
                'sometimes',
                'nullable',
                'boolean',
            ],
            $prefix.'notice_period_days' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0',
                'max:365',
            ],
            $prefix.'current_salary' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
            ],
            $prefix.'current_currency_id' => [
                'sometimes',
                'nullable',
                Rule::exists('currencies', 'id')->where('is_active', true),
            ],
            $prefix.'expected_salary_min' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
            ],
            $prefix.'expected_salary_max' => [
                'sometimes',
                'nullable',
                'numeric',
                'gte:'.$prefix.'expected_salary_min',
            ],
            $prefix.'expected_currency_id' => [
                'sometimes',
                'nullable',
                Rule::exists('currencies', 'id')->where('is_active', true),
            ],
            $prefix.'cv' => [
                'sometimes',
                'nullable',
                'file',
                'mimes:pdf,doc,docx',
                'max:10240',
            ],
        ];
    }

    private function requiredFields(
        OnboardingStepEnum $step,
        array $visibleFields,
        array $requiredFields,
        array $context,
    ): array {
        $experiencesField = ProfessionalFormFieldEnum::EXPERIENCES->value;
        $experiencesAreRequired = $step === OnboardingStepEnum::SKILLS_AND_EXPERIENCES
            && (int) ($context['years_of_experience'] ?? 0) > 0
            && in_array($experiencesField, $visibleFields, true);

        if ($experiencesAreRequired) {
            $requiredFields[] = $experiencesField;
        }

        return array_values(array_unique($requiredFields));
    }

    private function applyFieldPolicy(
        array $rules,
        OnboardingStepEnum $step,
        string $prefix,
        array $visibleFields,
        array $requiredFields,
        bool $enforceRequiredFields,
    ): array {
        $configuredRules = [];

        foreach (ProfessionalFormFieldEnum::cases() as $field) {
            if ($field->step() !== $step->value) {
                continue;
            }

            if (! in_array($field->value, $visibleFields, true)) {
                foreach ($field->payloadKeys() as $payloadKey) {
                    $configuredRules[$prefix.$payloadKey] = ['prohibited'];
                }

                continue;
            }

            foreach ($field->payloadKeys() as $payloadKey) {
                $rootKey = $prefix.$payloadKey;

                foreach ($rules as $ruleKey => $fieldRules) {
                    $belongsToField = $ruleKey === $rootKey
                        || str_starts_with($ruleKey, $rootKey.'.');

                    if (! $belongsToField) {
                        continue;
                    }

                    $shouldBeRequired = $enforceRequiredFields
                        && $ruleKey === $rootKey
                        && in_array($field->value, $requiredFields, true);

                    $configuredRules[$ruleKey] = $shouldBeRequired
                        ? $this->required($fieldRules)
                        : $fieldRules;
                }
            }
        }

        return $configuredRules;
    }

    private function required(array $rules): array
    {
        $requiredRules = array_values(array_filter(
            $rules,
            fn (mixed $rule): bool => ! in_array(
                $rule,
                ['sometimes', 'nullable', 'required'],
                true,
            ),
        ));

        array_unshift($requiredRules, 'required');

        if (
            in_array('array', $requiredRules, true)
            && ! in_array('min:1', $requiredRules, true)
        ) {
            $requiredRules[] = 'min:1';
        }

        return $requiredRules;
    }
}
