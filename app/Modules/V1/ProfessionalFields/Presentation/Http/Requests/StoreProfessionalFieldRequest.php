<?php

namespace App\Modules\V1\ProfessionalFields\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProfessionalFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'array:en,ar'],
            'name.en' => ['required', 'string', 'max:255'],
            'name.ar' => ['required', 'string', 'max:255'],
            'description' => ['required', 'array:en,ar'],
            'description.en' => ['required', 'string', 'max:1000'],
            'description.ar' => ['required', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
            'form_fields' => ['present', 'array'],
            'form_fields.*' => [
                'required',
                'string',
                'distinct',
                Rule::exists('professional_form_fields', 'key'),
            ],
        ];
    }
}
