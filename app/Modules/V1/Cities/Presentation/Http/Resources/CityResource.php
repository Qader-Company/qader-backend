<?php

namespace App\Modules\V1\Cities\Presentation\Http\Resources;

use App\Modules\V1\Countries\Presentation\Http\Resources\CountryResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'country_id' => $this->country_id,
            'name' => $this->nameFor($request),
            'is_active' => $this->is_active,
            'country' => new CountryResource($this->whenLoaded('country')),
        ];
    }

    private function nameFor(Request $request): string|array|null
    {
        if (! $request->is('api/v1/admin/*')) {
            return $this->rawValue('localized_name') ?? $this->name;
        }

        return [
            'en' => $this->translate('en', false)?->name,
            'ar' => $this->translate('ar', false)?->name,
        ];
    }

    private function rawValue(string $key): mixed
    {
        if ($this->resource instanceof Model) {
            return $this->resource->getRawOriginal($key);
        }

        return $this->resource->{$key} ?? null;
    }
}
