<?php

namespace App\Modules\V1\Skills\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListActiveSkillsRequest extends FormRequest
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
        ];
    }
}
