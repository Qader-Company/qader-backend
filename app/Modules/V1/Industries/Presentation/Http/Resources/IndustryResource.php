<?php

namespace App\Modules\V1\Industries\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IndustryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $request->is('api/v1/admin/*') ? [
                'en' => $this->translate('en', false)?->name,
                'ar' => $this->translate('ar', false)?->name,
            ] : $this->name,
            'is_active' => $this->is_active,
        ];
    }
}
