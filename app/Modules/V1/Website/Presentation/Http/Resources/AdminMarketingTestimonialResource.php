<?php

namespace App\Modules\V1\Website\Presentation\Http\Resources;

use App\Support\Http\Resources\AdminFormInput;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminMarketingTestimonialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'inputs' => [
                AdminFormInput::make('name_ar', $this->translate('ar', false)?->name, 'text'),
                AdminFormInput::make('name_en', $this->translate('en', false)?->name, 'text'),
                AdminFormInput::make('role_ar', $this->translate('ar', false)?->role, 'text'),
                AdminFormInput::make('role_en', $this->translate('en', false)?->role, 'text'),
                AdminFormInput::make('quote_ar', $this->translate('ar', false)?->quote, 'textarea'),
                AdminFormInput::make('quote_en', $this->translate('en', false)?->quote, 'textarea'),
                AdminFormInput::make('avatar', $this->getFirstMediaUrl('avatar') ?: null, 'file'),
                AdminFormInput::make('display_order', $this->display_order, 'number'),
                AdminFormInput::make('is_active', $this->is_active, 'checkbox'),
            ],
        ];
    }
}
