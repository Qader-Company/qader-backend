<?php

namespace App\Modules\V1\Website\Presentation\Http\Resources;

use App\Support\Localization\LocalizedValue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicMarketingFaqResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question' => LocalizedValue::resolve(
                $this->translate('en', false)?->question,
                $this->translate('ar', false)?->question,
            ),
            'answer' => LocalizedValue::resolve(
                $this->translate('en', false)?->answer,
                $this->translate('ar', false)?->answer,
            ),
        ];
    }
}
