<?php

namespace App\Modules\V1\Currencies\Presentation\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CurrencyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->nameFor($request),
            'symbol' => $this->symbol,
            'is_active' => (bool) $this->is_active,
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
