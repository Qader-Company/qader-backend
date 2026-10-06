<?php

namespace App\Modules\V1\AccessControl\Presentation\Http\Resources;

use App\Modules\V1\AccessControl\Application\Services\PermissionRegistry;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class PermissionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $portal = PortalTypeEnum::tryFrom($this->portal);
        $permission = $portal === null
            ? null
            : PermissionRegistry::find($portal, $this->name);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'portal' => $this->portal,
            'label' => $permission?->label()
                ?? Str::headline($this->name),
        ];
    }
}
