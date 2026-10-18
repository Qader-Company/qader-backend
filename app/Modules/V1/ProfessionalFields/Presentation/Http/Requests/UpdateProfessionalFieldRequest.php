<?php

namespace App\Modules\V1\ProfessionalFields\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfessionalFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'array:en,ar'],
            'name.en' => ['required_with:name', 'string', 'max:255'],
            'name.ar' => ['required_with:name', 'string', 'max:255'],
            'description' => ['sometimes', 'array:en,ar'],
            'description.en' => ['required_with:description', 'string', 'max:1000'],
            'description.ar' => ['required_with:description', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
            'form_fields' => ['sometimes', 'array'],
            'form_fields.*' => [
                'required',
                'string',
                'distinct',
                Rule::exists('professional_form_fields', 'key'),
            ],
        ];
    }
}
