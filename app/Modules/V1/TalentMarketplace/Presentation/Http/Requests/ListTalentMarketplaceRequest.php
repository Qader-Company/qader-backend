<?php

namespace App\Modules\V1\TalentMarketplace\Presentation\Http\Requests;

use App\Modules\V1\Cities\Domain\Models\City;
use App\Modules\V1\Talents\Domain\ValueObjects\LanguageProficiencyEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\SeniorityLevelEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\WorkModeEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\WorkTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ListTalentMarketplaceRequest extends FormRequest
{
    public const SORTS = [
        'highest_match',
        'newest',
        'experience_high_to_low',
        'salary_low_to_high',
        'salary_high_to_low',
    ];

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'job_title_id' => ['nullable', 'integer', Rule::exists('job_titles', 'id')->where('is_active', true)],
            'seniority_level' => ['nullable', 'array'],
            'seniority_level.*' => ['distinct', Rule::enum(SeniorityLevelEnum::class)],
            'years_of_experience_min' => ['nullable', 'integer', 'min:0', 'max:100'],
            'salary_min' => ['nullable', 'numeric', 'min:0'],
            'salary_max' => ['nullable', 'numeric', 'min:0', 'gte:salary_min'],
            'currency_id' => ['nullable', 'integer', Rule::exists('currencies', 'id')->where('is_active', true)],
            'skill_ids' => ['nullable', 'array'],
            'skill_ids.*' => ['integer', 'distinct', Rule::exists('skills', 'id')->where('is_active', true)],
            'tool_ids' => ['nullable', 'array'],
            'tool_ids.*' => ['integer', 'distinct', Rule::exists('tools', 'id')->where('is_active', true)],
            'work_types' => ['nullable', 'array'],
            'work_types.*' => ['distinct', Rule::enum(WorkTypeEnum::class)],
            'work_modes' => ['nullable', 'array'],
            'work_modes.*' => ['distinct', Rule::enum(WorkModeEnum::class)],
            'notice_period_days_max' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'country_id' => ['nullable', 'integer', Rule::exists('countries', 'id')->where('is_active', true)],
            'city_id' => ['nullable', 'integer', Rule::exists('cities', 'id')->where('is_active', true)],
            'language_id' => ['nullable', 'integer', 'required_with:language_proficiency_level', Rule::exists('languages', 'id')->where('is_active', true)],
            'language_proficiency_level' => ['nullable', 'required_with:language_id', Rule::enum(LanguageProficiencyEnum::class)],
            'sort' => ['nullable', Rule::in(self::SORTS)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $sort = $this->input('sort');
            $usesSalary = $this->filled('salary_min')
                || $this->filled('salary_max')
                || in_array($sort, ['salary_low_to_high', 'salary_high_to_low'], true);

            if ($usesSalary && ! $this->filled('currency_id')) {
                $validator->errors()->add(
                    'currency_id',
                    __('marketplace.validation.currency_required_for_salary'),
                );
            }

            if ($this->filled('city_id') && $this->filled('country_id')) {
                $validCity = City::query()
                    ->whereKey($this->integer('city_id'))
                    ->where('country_id', $this->integer('country_id'))
                    ->exists();

                if (! $validCity) {
                    $validator->errors()->add('city_id', __('marketplace.validation.city_country_mismatch'));
                }
            }
        }];
    }
}
