<?php

namespace App\Modules\V1\Languages\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListActiveLanguagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
        ];
    }
}
