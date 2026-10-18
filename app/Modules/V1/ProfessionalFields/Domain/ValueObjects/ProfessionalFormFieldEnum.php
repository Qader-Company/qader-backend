<?php

namespace App\Modules\V1\ProfessionalFields\Domain\ValueObjects;

enum ProfessionalFormFieldEnum: string
{
    case PROFESSIONAL_FIELD = 'professional_field_id';
    case FULL_NAME = 'full_name';
    case EMAIL = 'email';
    case PROFESSIONAL_SUMMARY = 'professional_summary';
    case PHONE_NUMBER = 'phone_number';
    case WHATSAPP_NUMBER = 'whatsapp_number';
    case DATE_OF_BIRTH = 'date_of_birth';
    case GENDER = 'gender';
    case RELIGION = 'religion';
    case NATIONALITY_COUNTRY = 'nationality_country_id';
    case RESIDENCE_COUNTRY = 'residence_country_id';
    case RESIDENCE_CITY = 'residence_city_id';
    case LANGUAGES = 'languages';

    case JOB_TITLE = 'job_title_id';
    case SENIORITY_LEVEL = 'seniority_level';
    case YEARS_OF_EXPERIENCE = 'years_of_experience';
    case SKILLS = 'skill_ids';
    case TOOLS = 'tool_ids';
    case LINKEDIN_URL = 'linkedin_url';
    case GITHUB_URL = 'github_url';
    case BEHANCE_URL = 'behance_url';
    case PORTFOLIO_URL = 'portfolio_url';
    case EXPERIENCES = 'experiences';
    case PROJECTS = 'projects';

    case WORK_TYPES = 'work_types';
    case WORK_MODES = 'work_modes';
    case WILLING_TO_RELOCATE = 'willing_to_relocate';
    case NOTICE_PERIOD = 'notice_period_days';
    case CURRENT_SALARY = 'current_salary';
    case EXPECTED_SALARY = 'expected_salary';
    case CV = 'cv';

    public const PERSONAL_INFORMATION = 'personal-information';

    public const SKILLS_AND_EXPERIENCES = 'skills-and-experiences';

    public const WORK_PREFERENCES = 'work-preferences';

    public static function values(): array
    {
        return array_map(
            static fn (self $field): string => $field->value,
            self::cases(),
        );
    }

    public function step(): string
    {
        return match ($this) {
            self::PROFESSIONAL_FIELD,
            self::FULL_NAME,
            self::EMAIL,
            self::PHONE_NUMBER,
            self::WHATSAPP_NUMBER,
            self::DATE_OF_BIRTH,
            self::GENDER,
            self::RELIGION,
            self::NATIONALITY_COUNTRY,
            self::RESIDENCE_COUNTRY,
            self::RESIDENCE_CITY,
            self::LANGUAGES,
            self::CV => self::PERSONAL_INFORMATION,

            self::PROFESSIONAL_SUMMARY,
            self::JOB_TITLE,
            self::SENIORITY_LEVEL,
            self::YEARS_OF_EXPERIENCE,
            self::SKILLS,
            self::TOOLS,
            self::LINKEDIN_URL,
            self::GITHUB_URL,
            self::BEHANCE_URL,
            self::PORTFOLIO_URL,
            self::EXPERIENCES,
            self::PROJECTS => self::SKILLS_AND_EXPERIENCES,

            self::WORK_TYPES,
            self::WORK_MODES,
            self::WILLING_TO_RELOCATE,
            self::NOTICE_PERIOD,
            self::CURRENT_SALARY,
            self::EXPECTED_SALARY => self::WORK_PREFERENCES,
        };
    }

    public function isRequiredByDefault(): bool
    {
        return in_array($this, [
            self::PROFESSIONAL_FIELD,
            self::FULL_NAME,
            self::EMAIL,
            self::PHONE_NUMBER,
            self::WHATSAPP_NUMBER,
            self::GENDER,
            self::JOB_TITLE,
            self::SENIORITY_LEVEL,
            self::YEARS_OF_EXPERIENCE,
            self::SKILLS,
            self::LINKEDIN_URL,
            self::WORK_TYPES,
            self::WORK_MODES,
            self::NOTICE_PERIOD,
            self::CURRENT_SALARY,
            self::EXPECTED_SALARY,
            self::CV,
        ], true);
    }

    public function isAlwaysRequired(): bool
    {
        return in_array($this, [
            self::PROFESSIONAL_FIELD,
            self::FULL_NAME,
            self::EMAIL,
        ], true);
    }

    public function payloadKeys(): array
    {
        return match ($this) {
            self::PHONE_NUMBER => [
                'phone_country_code',
                'phone_number',
            ],
            self::WHATSAPP_NUMBER => [
                'whatsapp_country_code',
                'whatsapp_number',
            ],
            self::CURRENT_SALARY => [
                'current_salary',
                'current_currency_id',
            ],
            self::EXPECTED_SALARY => [
                'expected_salary_min',
                'expected_salary_max',
                'expected_currency_id',
            ],
            default => [$this->value],
        };
    }

    public function responseKeys(): array
    {
        return match ($this) {
            self::PROFESSIONAL_FIELD => ['professional_field'],
            self::PHONE_NUMBER => [
                'phone_country_code',
                'phone_number',
            ],
            self::WHATSAPP_NUMBER => [
                'whatsapp_country_code',
                'whatsapp_number',
            ],
            self::NATIONALITY_COUNTRY => ['nationality_country'],
            self::RESIDENCE_COUNTRY => ['residence_country'],
            self::RESIDENCE_CITY => ['residence_city'],
            self::JOB_TITLE => ['job_title'],
            self::SKILLS => ['skills'],
            self::TOOLS => ['tools'],
            self::CURRENT_SALARY => [
                'current_salary',
                'current_currency',
            ],
            self::EXPECTED_SALARY => [
                'expected_salary_min',
                'expected_salary_max',
                'expected_currency',
            ],
            default => [$this->value],
        };
    }
}
