<?php

namespace App\Modules\V1\Website\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMarketingTestimonialRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'role_ar' => ['nullable', 'string', 'max:255'],
            'role_en' => ['nullable', 'string', 'max:255'],
            'quote_ar' => ['nullable', 'string', 'max:5000'],
            'quote_en' => ['nullable', 'string', 'max:5000'],
            'avatar' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
            'display_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'name_ar' => ['example' => 'تميم محمد'],
            'name_en' => ['example' => 'Tamem Mohamed'],
            'role_ar' => ['example' => 'مطور باك إند مبتدئ'],
            'role_en' => ['example' => 'jr.Back End Developer'],
            'quote_ar' => ['example' => 'أنا سعيد بكوني جزءًا من قادر.'],
            'quote_en' => ['example' => "I'm happy to be part of Qader."],
            'avatar' => ['description' => 'Optional testimonial avatar.', 'type' => 'file'],
            'display_order' => ['example' => 1],
            'is_active' => ['example' => true],
        ];
    }
}
