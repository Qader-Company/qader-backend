<?php

namespace App\Modules\V1\ProfessionalFields\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListProfessionalFormFieldsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'professional_field_id' => [
                'required',
                'integer',
                Rule::exists('professional_fields', 'id')
                    ->where('is_active', true),
            ],
        ];
    }
}
