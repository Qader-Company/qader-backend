<?php

namespace App\Modules\V1\JobForms\Presentation\Http\Requests;

use App\Modules\V1\JobForms\Domain\ValueObjects\JobWorkModeEnum;
use App\Modules\V1\JobForms\Domain\ValueObjects\JobWorkTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListPublicJobsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'professional_field_id' => ['nullable', 'integer', Rule::exists('professional_fields', 'id')->where('is_active', true)],
            'skill_id' => ['nullable', 'integer', Rule::exists('skills', 'id')->where('is_active', true)],
            'work_type' => ['nullable', Rule::enum(JobWorkTypeEnum::class)],
            'work_mode' => ['nullable', Rule::enum(JobWorkModeEnum::class)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
