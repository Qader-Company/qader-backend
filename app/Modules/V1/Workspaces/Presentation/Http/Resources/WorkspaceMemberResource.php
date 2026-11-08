<?php

namespace App\Modules\V1\Workspaces\Presentation\Http\Resources;

use App\Modules\V1\AccessControl\Presentation\Http\Resources\WorkspaceRoleResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkspaceMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $this->client?->user;

        return ['id' => $this->id, 'client_id' => $this->client_id, 'name' => $user ? trim($user->first_name.' '.$user->last_name) : null, 'email' => $user?->email, 'avatar' => $user?->avatar, 'status' => $this->status->value, 'joined_at' => $this->joined_at, 'roles' => WorkspaceRoleResource::collection($this->whenLoaded('roles'))];
    }
}
