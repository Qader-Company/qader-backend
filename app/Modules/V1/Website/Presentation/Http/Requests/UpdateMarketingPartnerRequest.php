<?php

namespace App\Modules\V1\Website\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMarketingPartnerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'logo' => ['sometimes', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:5120'],
            'url' => ['sometimes', 'nullable', 'url:http,https', 'max:2048'],
            'display_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function bodyParameters(): array
    {
        return (new StoreMarketingPartnerRequest)->bodyParameters();
    }
}
