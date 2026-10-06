<?php

namespace App\Modules\V1\Clients\Presentation\Http\Requests;

use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterClientRequest extends FormRequest
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
                Rule::unique('users', 'email')->where('type', PortalTypeEnum::CLIENT->value),
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
