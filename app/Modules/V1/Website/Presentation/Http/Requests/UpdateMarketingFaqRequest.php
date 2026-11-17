<?php

namespace App\Modules\V1\Website\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMarketingFaqRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'question_ar' => ['sometimes', 'required', 'string', 'max:255'],
            'question_en' => ['sometimes', 'nullable', 'string', 'max:255'],
            'answer_ar' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'answer_en' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'display_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function bodyParameters(): array
    {
        return (new StoreMarketingFaqRequest)->bodyParameters();
    }
}
