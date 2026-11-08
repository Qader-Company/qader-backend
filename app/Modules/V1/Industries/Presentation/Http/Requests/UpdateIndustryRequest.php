<?php

namespace App\Modules\V1\Industries\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIndustryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'slug' => ['sometimes', 'string', 'max:100', 'alpha_dash:ascii', Rule::unique('industries', 'slug')->ignore($this->route('industry'))],
            'name' => ['sometimes', 'array:en,ar'],
            'name.en' => ['required_with:name', 'string', 'max:255'],
            'name.ar' => ['required_with:name', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
