<?php

namespace App\Modules\V1\Authentication\Presentation\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge(
            (new EmailValidationRequest)->rules(),
            [
                'password' => ['required', 'string', 'min:8'],
                'fcm_token' => ['nullable', 'string', 'max:4096'],
                'platform' => [
                    'nullable',
                    'required_with:fcm_token',
                    Rule::in(['web', 'ios', 'android']),
                ],
            ]
        );
    }
}
