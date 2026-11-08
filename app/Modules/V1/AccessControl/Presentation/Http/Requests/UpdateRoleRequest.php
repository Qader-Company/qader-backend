<?php

namespace App\Modules\V1\AccessControl\Presentation\Http\Requests;

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\AccessControl\Presentation\Http\Rules\NotSuperAdminRoleName;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name_en' => [
                'sometimes',
                'string',
                'max:255',
                new NotSuperAdminRoleName,
                Rule::unique('roles', 'name_en')
                    ->ignore($this->route('role'))
                    ->where('portal', PortalTypeEnum::ADMIN->value),
            ],
            'name_ar' => ['sometimes', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'permissions' => ['sometimes', 'array', 'min:1'],
            'permissions.*' => [
                'required',
                'string',
                'distinct',
                Rule::in(AdminPermissionEnum::values()),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge(['is_active' => $this->boolean('is_active')]);
        }
    }
}
