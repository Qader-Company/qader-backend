<?php

namespace App\Modules\V1\Admins\Presentation\Http\Resources;

use App\Modules\V1\Users\Application\Services\UserAvatarUrlResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => trim($this->first_name.' '.$this->last_name),
            'display_name_ar' => $this->display_name_ar,
            'email' => $this->email,
            'phone_country_code' => $this->phone_country_code,
            'phone_number' => $this->phone_number,
            'avatar' => UserAvatarUrlResolver::resolve($this->avatar),
            'is_active' => (bool) $this->is_active,
            'email_verified_at' => $this->email_verified_at,
            'last_login_at' => $this->last_login_at,
            'roles' => $this->roles,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
