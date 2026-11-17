<?php

namespace App\Modules\V1\Website\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHiringSupportRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone_country_code' => ['required', 'string', 'regex:/^\+[1-9]\d{0,3}$/'],
            'phone_number' => ['required', 'string', 'regex:/^[0-9][0-9\s().-]{5,23}$/'],
            'country_id' => ['required', 'integer', Rule::exists('countries', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'description' => ['required', 'string', 'max:5000'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'name' => ['description' => 'Full name of the requester.', 'example' => 'Ahmed Hassan'],
            'email' => ['description' => 'Contact email.', 'example' => 'ahmed@example.com'],
            'phone_country_code' => ['description' => 'International dialling code.', 'example' => '+20'],
            'phone_number' => ['description' => 'Phone number without the country code.', 'example' => '1001234567'],
            'country_id' => ['description' => 'Active country ID from public master data.', 'example' => 1],
            'description' => ['description' => 'How Qader can help with the hiring need.', 'example' => 'We need help building a dedicated engineering team.'],
        ];
    }
}
