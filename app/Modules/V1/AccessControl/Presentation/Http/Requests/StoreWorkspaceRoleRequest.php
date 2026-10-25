<?php

namespace App\Modules\V1\AccessControl\Presentation\Http\Requests;

use App\Modules\V1\AccessControl\Application\Services\PermissionRegistry;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkspaceRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['name_en' => ['required', 'string', 'max:255'], 'name_ar' => ['nullable', 'string', 'max:255'], 'is_active' => ['sometimes', 'boolean'], 'permissions' => ['required', 'array'], 'permissions.*' => ['string', Rule::in(PermissionRegistry::names(PortalTypeEnum::CLIENT))]];
    }
}
