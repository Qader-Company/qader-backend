<?php

namespace App\Modules\V1\ProfessionalFields\Presentation\Http\Resources;

use App\Support\Http\Resources\ResolvesLocalizedResourceValues;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfessionalFieldResource extends JsonResource
{
    use ResolvesLocalizedResourceValues;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->localizedOrTranslations($request, 'name'),
            'description' => $this->localizedOrTranslations($request, 'description'),
            'slug' => $this->slug,
            'is_active' => $this->when(
                isset($this->is_active),
                fn () => (bool) $this->is_active,
            ),
        ];
    }
}
