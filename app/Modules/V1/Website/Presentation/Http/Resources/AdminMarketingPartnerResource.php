<?php

namespace App\Modules\V1\Website\Presentation\Http\Resources;

use App\Support\Http\Resources\AdminFormInput;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminMarketingPartnerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'inputs' => [
                AdminFormInput::make('name', $this->name, 'text'),
                AdminFormInput::make('logo', $this->getFirstMediaUrl('logo') ?: null, 'file'),
                AdminFormInput::make('url', $this->url, 'url'),
                AdminFormInput::make('display_order', $this->display_order, 'number'),
                AdminFormInput::make('is_active', $this->is_active, 'checkbox'),
            ],
        ];
    }
}
