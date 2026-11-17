<?php

namespace App\Modules\V1\Website\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicMarketingPartnerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'logo_url' => $this->getFirstMediaUrl('logo') ?: null,
            'url' => $this->url,
        ];
    }
}
