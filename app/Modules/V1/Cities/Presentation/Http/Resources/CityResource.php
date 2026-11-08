<?php

namespace App\Modules\V1\Cities\Presentation\Http\Resources;

use App\Modules\V1\Countries\Presentation\Http\Resources\CountryResource;
use App\Support\Http\Resources\ResolvesLocalizedResourceValues;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CityResource extends JsonResource
{
    use ResolvesLocalizedResourceValues;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'country_id' => $this->country_id,
            'name' => $this->localizedOrTranslations($request, 'name'),
            'is_active' => $this->is_active,
            'country' => new CountryResource($this->whenLoaded('country')),
        ];
    }
}
