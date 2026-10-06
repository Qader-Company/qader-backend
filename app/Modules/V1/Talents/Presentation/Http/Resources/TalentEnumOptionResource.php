<?php

namespace App\Modules\V1\Talents\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TalentEnumOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'value' => $this->resource['value'],
            'label' => $this->resource['label'],
        ];
    }
}
