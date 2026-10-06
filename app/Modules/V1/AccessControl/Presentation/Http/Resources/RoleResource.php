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
            'portal' => $this->portal,
            'permissions' => PermissionResource::collection(
                $this->permissions ?? []
            ),
        ];
    }
}
