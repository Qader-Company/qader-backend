<?php

namespace App\Modules\V1\Admins\Presentation\Http\Requests;

use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminRoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = DB::table('admins')
            ->where('id', $this->route('admin'))
            ->value('user_id');

        return [
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['sometimes', 'string', 'max:100'],
            'display_name_ar' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->ignore($userId)
                    ->where('type', PortalTypeEnum::ADMIN->value),
            ],
            'password' => [
                'sometimes',
                'string',
                'confirmed',
                Password::min(8)->mixedCase(),
            ],
            'avatar' => [
                'sometimes',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
            'phone_country_code' => ['sometimes', 'nullable', 'string', 'max:5'],
            'phone_number' => ['sometimes', 'nullable', 'string', 'max:20'],
            'role_ids' => ['sometimes', 'array', 'min:1'],
            'role_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('roles', 'id')
                    ->where(function ($query): void {
                        $query
                            ->where('portal', PortalTypeEnum::ADMIN->value)
                            ->where('name', '!=', AdminRoleEnum::SUPER_ADMIN->value)
                            ->where('is_active', true);
                    }),
            ],
        ];
    }
}
