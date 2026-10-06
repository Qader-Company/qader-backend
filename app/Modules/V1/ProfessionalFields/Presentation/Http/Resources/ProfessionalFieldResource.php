<?php

namespace App\Modules\V1\ProfessionalFields\Presentation\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfessionalFieldResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->nameFor($request),
            'slug' => $this->slug,
            'is_active' => $this->when(
                isset($this->is_active),
                fn () => (bool) $this->is_active,
            ),
        ];
    }

    private function nameFor(Request $request): string|array|null
    {
        if (! $request->is('api/v1/admin/*')) {
            return $this->rawValue('localized_name')
                ?? $this->rawValue('name')
                ?? $this->name;
        }

        return [
            'en' => $this->rawValue('name_en')
                ?? $this->translationName('en'),
            'ar' => $this->rawValue('name_ar')
                ?? $this->translationName('ar'),
        ];
    }

    private function translationName(string $locale): ?string
    {
        if (! $this->resource instanceof Model) {
            return null;
        }

        return $this->resource->translate($locale, false)?->name;
    }

    private function rawValue(string $key): mixed
    {
        if ($this->resource instanceof Model) {
            return $this->resource->getRawOriginal($key);
        }

        return $this->resource->{$key} ?? null;
    }
}
