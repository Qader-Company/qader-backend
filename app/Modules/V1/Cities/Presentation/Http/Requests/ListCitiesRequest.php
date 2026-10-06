<?php

namespace App\Modules\V1\Cities\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListCitiesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'country_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
