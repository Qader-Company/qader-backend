<?php

namespace App\Support\Http\Resources;

use App\Support\Localization\LocalizedValue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait ResolvesLocalizedResourceValues
{
    protected function localizedOrTranslations(
        Request $request,
        string $attribute,
        ?string $localizedKey = null,
        ?string $englishKey = null,
        ?string $arabicKey = null,
    ): mixed {
        if ($this->isAdminRequest($request)) {
            return [
                'en' => $this->rawResourceValue($englishKey ?? "{$attribute}_en")
                    ?? $this->translatedValue('en', $attribute),
                'ar' => $this->rawResourceValue($arabicKey ?? "{$attribute}_ar")
                    ?? $this->translatedValue('ar', $attribute),
            ];
        }

        $localized = $this->rawResourceValue($localizedKey ?? "localized_{$attribute}");
        if ($localized !== null) {
            return $localized;
        }

        if ($this->resource instanceof Model) {
            return $this->resource->{$attribute};
        }

        return $this->resource->{$attribute} ?? null;
    }

    protected function localizedValue(mixed $english, mixed $arabic): mixed
    {
        return LocalizedValue::resolve($english, $arabic);
    }

    protected function localizedTranslation(mixed $translations): mixed
    {
        if (! is_array($translations)) {
            return $translations;
        }

        return $this->localizedValue(
            $translations['en'] ?? null,
            $translations['ar'] ?? null,
        );
    }

    protected function isAdminRequest(Request $request): bool
    {
        return $request->is('api/v1/admin/*');
    }

    private function translatedValue(string $locale, string $attribute): mixed
    {
        if (! $this->resource instanceof Model || ! method_exists($this->resource, 'translate')) {
            return null;
        }

        return $this->resource->translate($locale, false)?->{$attribute};
    }

    private function rawResourceValue(string $key): mixed
    {
        if ($this->resource instanceof Model) {
            return $this->resource->getRawOriginal($key);
        }

        return $this->resource->{$key} ?? null;
    }
}
