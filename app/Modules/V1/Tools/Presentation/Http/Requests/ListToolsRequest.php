<?php

namespace App\Modules\V1\Tools\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListToolsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'professional_field_id' => [
                'nullable',
                'integer',
                'exists:professional_fields,id',
            ],
            'is_active' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
