<?php

namespace App\Modules\V1\JobTitles\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJobTitleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'professional_field_ids' => ['required', 'array', 'min:1'],
            'professional_field_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('professional_fields', 'id'),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
