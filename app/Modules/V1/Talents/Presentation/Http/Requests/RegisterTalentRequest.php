<?php

namespace App\Modules\V1\Talents\Presentation\Http\Requests;

use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterTalentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->where('type', PortalTypeEnum::TALENT->value),
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
