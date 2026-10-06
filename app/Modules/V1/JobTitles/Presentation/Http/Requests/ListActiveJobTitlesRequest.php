<?php

namespace App\Modules\V1\JobTitles\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListActiveJobTitlesRequest extends FormRequest
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
