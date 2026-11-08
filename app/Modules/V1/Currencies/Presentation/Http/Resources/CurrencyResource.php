<?php

namespace App\Modules\V1\Currencies\Presentation\Http\Resources;

use App\Support\Http\Resources\ResolvesLocalizedResourceValues;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CurrencyResource extends JsonResource
{
    use ResolvesLocalizedResourceValues;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->localizedOrTranslations($request, 'name'),
            'symbol' => $this->symbol,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
