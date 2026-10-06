<?php

namespace App\Modules\V1\Admins\Presentation\Http\Requests;

use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreAdminRequest extends FormRequest
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
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->where('type', PortalTypeEnum::ADMIN->value),
            ],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)->mixedCase(),
            ],
            'avatar' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('roles', 'id')
                    ->where(function ($query): void {
                        $query
                            ->where('portal', PortalTypeEnum::ADMIN->value)
                            ->where('name', '!=', 'owner');
                    }),
            ],
        ];
    }
}
