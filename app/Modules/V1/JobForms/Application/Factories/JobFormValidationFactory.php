<?php

namespace App\Modules\V1\JobForms\Application\Factories;

use App\Modules\V1\JobForms\Domain\ValueObjects\JobExperienceRangeEnum;
use App\Modules\V1\ProfessionalFields\Domain\ValueObjects\ProfessionalFormFieldEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\GenderEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\LanguageProficiencyEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\ReligionEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\SeniorityLevelEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\WorkModeEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\WorkTypeEnum;
use Illuminate\Validation\Rule;

class JobFormValidationFactory
{
    public function rulesFor(ProfessionalFormFieldEnum $field, int $professionalFieldId): array
    {
        $key = 'answers.'.$field->value;

        return match ($field) {
            ProfessionalFormFieldEnum::YEARS_OF_EXPERIENCE => [$key => ['required', Rule::enum(JobExperienceRangeEnum::class)]],
            ProfessionalFormFieldEnum::SKILLS => [$key => ['required', 'array', 'min:1'], $key.'.*' => ['integer', 'distinct', Rule::exists('skills', 'id')->where('is_active', true), Rule::exists('professional_field_skill', 'skill_id')->where('professional_field_id', $professionalFieldId)]],
            ProfessionalFormFieldEnum::NOTICE_PERIOD => [$key => ['required', 'string', 'max:100']],
            ProfessionalFormFieldEnum::EXPECTED_SALARY => [$key => ['required', 'array:amount,currency_id'], $key.'.amount' => ['required', 'numeric', 'min:0'], $key.'.currency_id' => ['required', 'integer', Rule::exists('currencies', 'id')->where('is_active', true)]],
            ProfessionalFormFieldEnum::PHONE_NUMBER => [$key => ['required', 'string', 'max:24']],
            ProfessionalFormFieldEnum::WHATSAPP_NUMBER => [$key => ['required', 'string', 'max:24']],
            ProfessionalFormFieldEnum::DATE_OF_BIRTH => [$key => ['required', 'date', 'before:today']],
            ProfessionalFormFieldEnum::GENDER => [$key => ['required', Rule::enum(GenderEnum::class)]],
            ProfessionalFormFieldEnum::RELIGION => [$key => ['required', Rule::enum(ReligionEnum::class)]],
            ProfessionalFormFieldEnum::SENIORITY_LEVEL => [$key => ['required', Rule::enum(SeniorityLevelEnum::class)]],
            ProfessionalFormFieldEnum::NATIONALITY_COUNTRY, ProfessionalFormFieldEnum::RESIDENCE_COUNTRY => [$key => ['required', 'integer', Rule::exists('countries', 'id')->where('is_active', true)]],
            ProfessionalFormFieldEnum::RESIDENCE_CITY => [$key => ['required', 'integer', Rule::exists('cities', 'id')->where('is_active', true)]],
            ProfessionalFormFieldEnum::TOOLS => [$key => ['required', 'array', 'min:1'], $key.'.*' => ['integer', 'distinct', Rule::exists('tools', 'id')->where('is_active', true), Rule::exists('professional_field_tool', 'tool_id')->where('professional_field_id', $professionalFieldId)]],
            ProfessionalFormFieldEnum::LANGUAGES => [$key => ['required', 'array', 'min:1'], $key.'.*.language_id' => ['required', 'integer', 'distinct', Rule::exists('languages', 'id')->where('is_active', true)], $key.'.*.proficiency_level' => ['required', Rule::enum(LanguageProficiencyEnum::class)]],
            ProfessionalFormFieldEnum::LINKEDIN_URL, ProfessionalFormFieldEnum::GITHUB_URL, ProfessionalFormFieldEnum::BEHANCE_URL, ProfessionalFormFieldEnum::PORTFOLIO_URL => [$key => ['required', 'url', 'max:255']],
            ProfessionalFormFieldEnum::WILLING_TO_RELOCATE => [$key => ['required', 'boolean']],
            ProfessionalFormFieldEnum::CURRENT_SALARY => [$key => ['required', 'array:amount,currency_id'], $key.'.amount' => ['required', 'numeric', 'min:0'], $key.'.currency_id' => ['required', 'integer', Rule::exists('currencies', 'id')->where('is_active', true)]],
            ProfessionalFormFieldEnum::WORK_TYPES => [$key => ['required', 'array', 'min:1'], $key.'.*' => ['required', 'distinct', Rule::enum(WorkTypeEnum::class)]],
            ProfessionalFormFieldEnum::WORK_MODES => [$key => ['required', 'array', 'min:1'], $key.'.*' => ['required', 'distinct', Rule::enum(WorkModeEnum::class)]],
            ProfessionalFormFieldEnum::EXPERIENCES, ProfessionalFormFieldEnum::PROJECTS => [$key => ['required', 'array', 'min:1']],
            default => [$key => ['required', 'string', 'max:5000']],
        };
    }
}
