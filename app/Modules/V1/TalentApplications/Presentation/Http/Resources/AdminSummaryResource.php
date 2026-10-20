<?php

namespace App\Modules\V1\TalentApplications\Presentation\Http\Resources;

use App\Modules\V1\Users\Application\Services\UserAvatarUrlResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $this->user;

        return [
            'id' => $this->id,
            'full_name' => trim(($user?->first_name ?? '').' '.($user?->last_name ?? '')),
            'display_name_ar' => $this->display_name_ar,
            'email' => $user?->email,
            'avatar' => UserAvatarUrlResolver::resolve($user?->avatar),
            'is_active' => (bool) $user?->is_active,
        ];
    }
}
