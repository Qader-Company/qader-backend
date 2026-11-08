<?php

namespace App\Modules\V1\AccessControl\Presentation\Http\Resources;

use App\Modules\V1\AccessControl\Domain\ValueObjects\ClientPermissionEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkspaceRoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $permissions = $this->key === 'owner'
            ? collect(ClientPermissionEnum::cases())->map(fn ($permission) => [
                'value' => $permission->value,
                'label' => $permission->label(),
            ])
            : $this->whenLoaded('permissions', fn () => $this->permissions->map(fn ($permission) => [
                'value' => $permission->name,
                'label' => __('permissions.'.$permission->name),
            ]));

        return [
            'id' => $this->id,
            'key' => $this->key,
            'name_en' => $this->name_en,
            'name_ar' => $this->name_ar,
            'is_active' => $this->is_active,
            'is_protected' => $this->key === 'owner',
            'members_count' => (int) ($this->members_count ?? 0),
            'permissions' => $permissions,
        ];
    }
}
