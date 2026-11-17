<?php

namespace App\Modules\V1\Website\Presentation\Http\Resources;

use App\Support\Localization\LocalizedValue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicMarketingTestimonialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => LocalizedValue::resolve(
                $this->translate('en', false)?->name,
                $this->translate('ar', false)?->name,
            ),
            'role' => LocalizedValue::resolve(
                $this->translate('en', false)?->role,
                $this->translate('ar', false)?->role,
            ),
            'quote' => LocalizedValue::resolve(
                $this->translate('en', false)?->quote,
                $this->translate('ar', false)?->quote,
            ),
            'avatar_url' => $this->getFirstMediaUrl('avatar') ?: null,
        ];
    }
}
