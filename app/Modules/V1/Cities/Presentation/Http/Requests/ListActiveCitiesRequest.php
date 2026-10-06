<?php

namespace App\Modules\V1\Cities\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListActiveCitiesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'country_id' => [
                'nullable',
                'integer',
            ],
        ];
    }
}
