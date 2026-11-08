<?php

namespace App\Modules\V1\TalentApplications\Presentation\Http\Requests;

use App\Modules\V1\TalentApplications\Domain\ValueObjects\EvaluationTipPriorityEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveTalentEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'technical_skills_rating' => ['required', 'integer', 'between:1,5'],
            'communication_skills_rating' => ['required', 'integer', 'between:1,5'],
            'problem_solving_rating' => ['required', 'integer', 'between:1,5'],
            'soft_skills_rating' => ['required', 'integer', 'between:1,5'],
            'recommended_salary_min' => ['nullable', 'required_with:recommended_salary_max,currency_id', 'numeric', 'min:0', 'lte:recommended_salary_max'],
            'recommended_salary_max' => ['nullable', 'required_with:recommended_salary_min,currency_id', 'numeric', 'min:0', 'gte:recommended_salary_min'],
            'currency_id' => [
                'nullable',
                'required_with:recommended_salary_min,recommended_salary_max',
                'integer',
                Rule::exists('currencies', 'id')->where('is_active', true),
            ],
            'hr_report' => ['nullable', 'string', 'max:10000'],
            'technical_report' => ['nullable', 'string', 'max:10000'],
            'psychology_report' => ['nullable', 'string', 'max:10000'],
            'tips' => ['nullable', 'array', 'max:20'],
            'tips.*.title' => ['required', 'string', 'max:255'],
            'tips.*.priority' => ['required', Rule::enum(EvaluationTipPriorityEnum::class)],
            'tips.*.description' => ['required', 'string', 'max:5000'],
        ];
    }
}
