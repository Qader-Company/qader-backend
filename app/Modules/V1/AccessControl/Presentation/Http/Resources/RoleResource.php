<?php

namespace App\Modules\V1\AccessControl\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'name_en' => $this->name_en,
            'name_ar' => $this->name_ar,
            'portal' => $this->portal,
            'is_active' => (bool) $this->is_active,
            'users_count' => (int) ($this->users_count ?? 0),
            'permissions' => PermissionResource::collection(
                $this->permissions ?? []
            ),
        ];
    }
}
