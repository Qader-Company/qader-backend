<?php

namespace App\Modules\V1\Users\Presentation\Http\Resources;

use App\Modules\V1\Users\Application\Services\UserAccessResolver;
use App\Modules\V1\Users\Application\Services\UserAvatarUrlResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $access = UserAccessResolver::resolve($this->resource);

        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'avatar' => UserAvatarUrlResolver::resolve($this->avatar),
            'type' => $this->type?->value,
            'email_verified_at' => $this->email_verified_at,
            'is_active' => (bool) $this->is_active,
            'last_login_at' => $this->last_login_at,
            'roles' => $access['roles'],
            'unassigned_permissions' => $access['unassigned_permissions'],
        ];
    }
}
