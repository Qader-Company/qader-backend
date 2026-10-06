<?php

namespace App\Modules\V1\Tools\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateToolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'professional_field_ids' => ['sometimes', 'array', 'min:1'],
            'professional_field_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('professional_fields', 'id'),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
