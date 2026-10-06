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
            'is_active' => ['sometimes', 'boolean'],
            'form_fields' => ['present', 'array'],
            'form_fields.*' => ['required', 'array:key,is_required'],
            'form_fields.*.key' => [
                'required',
                'string',
                'distinct',
                Rule::exists('professional_form_fields', 'key'),
            ],
            'form_fields.*.is_required' => ['required', 'boolean'],
        ];
    }
}
