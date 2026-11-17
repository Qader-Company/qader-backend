<?php

namespace App\Modules\V1\Website\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMarketingFaqRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'question_ar' => ['required', 'string', 'max:255'],
            'question_en' => ['nullable', 'string', 'max:255'],
            'answer_ar' => ['nullable', 'string', 'max:5000'],
            'answer_en' => ['nullable', 'string', 'max:5000'],
            'display_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'question_ar' => ['example' => 'ما الفرق بين خدمات قادر الثلاث؟'],
            'question_en' => ['example' => 'What is the difference between Qader services?'],
            'answer_ar' => ['example' => 'تختلف الخدمة حسب احتياج شركتك.'],
            'answer_en' => ['example' => 'The service depends on your company needs.'],
            'display_order' => ['example' => 1],
            'is_active' => ['example' => true],
        ];
    }
}
