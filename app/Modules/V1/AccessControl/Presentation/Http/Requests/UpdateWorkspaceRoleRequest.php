<?php

namespace App\Modules\V1\AccessControl\Presentation\Http\Requests;

use App\Modules\V1\AccessControl\Application\Services\PermissionRegistry;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkspaceRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['name_en' => ['sometimes', 'string', 'max:255'], 'name_ar' => ['sometimes', 'nullable', 'string', 'max:255'], 'is_active' => ['sometimes', 'boolean'], 'permissions' => ['sometimes', 'array'], 'permissions.*' => ['string', Rule::in(PermissionRegistry::names(PortalTypeEnum::CLIENT))]];
    }
}
