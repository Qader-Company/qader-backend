<?php

namespace App\Modules\V1\TalentApplications\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveInterviewEvaluationRequest extends FormRequest
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
            'hr_report' => ['nullable', 'string', 'max:10000'],
            'technical_report' => ['nullable', 'string', 'max:10000'],
            'psychology_report' => ['nullable', 'string', 'max:10000'],
            'final_feedback' => ['required', 'string', 'max:10000'],
        ];
    }
}
