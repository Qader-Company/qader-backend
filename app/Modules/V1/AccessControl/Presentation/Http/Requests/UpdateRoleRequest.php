<?php

namespace App\Modules\V1\AccessControl\Presentation\Http\Requests;

use App\Modules\V1\AccessControl\Domain\ValueObjects\AdminPermissionEnum;
use App\Modules\V1\AccessControl\Presentation\Http\Rules\NotOwnerRoleName;
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
            'name' => [
                'required',
                'string',
                'max:100',
                new NotOwnerRoleName,
                Rule::unique('roles', 'name')
                    ->ignore($this->route('role'))
                    ->where('portal', PortalTypeEnum::ADMIN->value),
            ],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => [
                'required',
                'string',
                'distinct',
                Rule::in(AdminPermissionEnum::values()),
            ],
        ];
    }
}
