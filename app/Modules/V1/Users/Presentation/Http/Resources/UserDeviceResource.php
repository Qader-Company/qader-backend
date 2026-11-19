<?php

namespace App\Modules\V1\Users\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserDeviceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'platform' => $this->platform,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
