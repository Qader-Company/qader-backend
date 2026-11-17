<?php

namespace App\Modules\V1\Website\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMarketingTestimonialRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name_ar' => ['sometimes', 'required', 'string', 'max:255'],
            'name_en' => ['sometimes', 'nullable', 'string', 'max:255'],
            'role_ar' => ['sometimes', 'nullable', 'string', 'max:255'],
            'role_en' => ['sometimes', 'nullable', 'string', 'max:255'],
            'quote_ar' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'quote_en' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'avatar' => ['sometimes', 'nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
            'display_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function bodyParameters(): array
    {
        return (new StoreMarketingTestimonialRequest)->bodyParameters();
    }
}
