<?php

namespace App\Modules\V1\JobForms\Presentation\Http\Requests;

use App\Modules\V1\JobForms\Domain\ValueObjects\JobPublishingStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListJobFormsRequest extends FormRequest
{
    public function rules(): array
    {
        return ['search' => ['nullable', 'string', 'max:100'], 'status' => ['nullable', Rule::enum(JobPublishingStatusEnum::class)], 'is_active' => ['nullable', 'boolean'], 'professional_field_id' => ['nullable', 'integer', 'exists:professional_fields,id'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100']];
    }
}
