<?php

namespace App\Modules\V1\Clients\Presentation\Http\Resources;

use App\Modules\V1\Users\Application\Services\UserAvatarUrlResolver;
use App\Support\ApiMetadata\EnumLabel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminWorkspaceMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $this->client?->user;
        $locale = app()->getLocale();

        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'full_name' => trim(($user?->first_name ?? '').' '.($user?->last_name ?? '')),
            'email' => $user?->email,
            'avatar' => UserAvatarUrlResolver::resolve($user?->avatar),
            // Shown in the Figma "Position" column; also the values behind the "Squads" filter.
            'roles' => $this->roles->map(fn ($role): array => [
                'id' => $role->id,
                'key' => $role->key,
                'name' => ($locale === 'ar' ? $role->name_ar : $role->name_en) ?: $role->name_en,
            ])->values(),
            'is_owner' => $this->roles->contains('key', 'owner'),
            'joined_at' => $this->joined_at,
            'status' => $this->status?->value,
            'status_label' => EnumLabel::for($this->status, 'workspace_member_statuses'),
            'available_actions' => $this->available_actions ?? [],
        ];
    }
}
