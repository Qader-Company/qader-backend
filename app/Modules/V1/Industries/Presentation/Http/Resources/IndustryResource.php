<?php

namespace App\Modules\V1\Industries\Presentation\Http\Resources;

use App\Support\Http\Resources\ResolvesLocalizedResourceValues;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IndustryResource extends JsonResource
{
    use ResolvesLocalizedResourceValues;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->localizedOrTranslations($request, 'name'),
            'is_active' => $this->is_active,
        ];
    }
}
