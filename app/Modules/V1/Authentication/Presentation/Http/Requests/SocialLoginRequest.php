<?php

namespace App\Modules\V1\Authentication\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SocialLoginRequest extends FormRequest
{
    public function rules(): array
    {
        return array_merge(
            [
                'token' => ['required', 'string'],
                'name' => ['sometimes', 'string', 'max:255'],
                'fcm_token' => ['nullable', 'string', 'max:4096'],
                'platform' => [
                    'nullable',
                    'required_with:fcm_token',
                    Rule::in(['web', 'ios', 'android']),
                ],
            ],
            config('social_auth.portal_profile_rules.'.$this->route('type'), [])
        );
    }
}
