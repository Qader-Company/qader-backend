<?php

namespace App\Modules\V1\Clients\Presentation\Http\Resources;

use App\Modules\V1\Clients\Domain\ValueObjects\ClientAccountStatusEnum;
use App\Modules\V1\Users\Application\Services\UserAvatarUrlResolver;
use App\Support\ApiMetadata\EnumLabel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminClientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $this->user;
        $status = $user?->is_active ? ClientAccountStatusEnum::ACTIVE : ClientAccountStatusEnum::SUSPENDED;
        $phone = trim(($this->phone_country_code ?? '').' '.($this->phone_number ?? ''));

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'full_name' => trim(($user?->first_name ?? '').' '.($user?->last_name ?? '')),
            'first_name' => $user?->first_name,
            'last_name' => $user?->last_name,
            'email' => $user?->email,
            'avatar' => UserAvatarUrlResolver::resolve($user?->avatar),
            'phone' => [
                'country_code' => $this->phone_country_code,
                'number' => $this->phone_number,
                'full' => $phone === '' ? null : $phone,
            ],
            'workspaces_count' => (int) ($this->owned_workspaces_count ?? 0),
            'registered_at' => $this->created_at,
            'status' => $status->value,
            'status_label' => EnumLabel::for($status, 'client_account_statuses'),
            'suspension' => $this->when($this->resource->offsetExists('suspension'), fn () => $this->suspension),
            'kpis' => $this->when($this->resource->offsetExists('kpis'), fn () => $this->kpis),
            'available_actions' => $this->available_actions ?? [],
        ];
    }
}
