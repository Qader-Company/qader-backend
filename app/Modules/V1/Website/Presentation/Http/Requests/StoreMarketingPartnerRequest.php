<?php

namespace App\Modules\V1\Website\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMarketingPartnerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'logo' => ['required', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:5120'],
            'url' => ['nullable', 'url:http,https', 'max:2048'],
            'display_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'name' => ['example' => 'Qader Partner'],
            'logo' => ['description' => 'Partner logo image.', 'type' => 'file'],
            'url' => ['example' => 'https://example.com'],
            'display_order' => ['example' => 1],
            'is_active' => ['example' => true],
        ];
    }
}
