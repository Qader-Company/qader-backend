<?php

namespace App\Modules\V1\JobForms\Presentation\Http\Requests;

use App\Modules\V1\JobForms\Domain\ValueObjects\JobSubmissionStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListJobSubmissionsRequest extends FormRequest
{
    public function rules(): array
    {
        return ['search' => ['nullable', 'string', 'max:100'], 'status' => ['nullable', Rule::enum(JobSubmissionStatusEnum::class)], 'submitted_from' => ['nullable', 'date'], 'submitted_to' => ['nullable', 'date', 'after_or_equal:submitted_from'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100']];
    }
}
