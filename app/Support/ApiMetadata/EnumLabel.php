<?php

namespace App\Support\ApiMetadata;

use BackedEnum;
use Illuminate\Support\Str;

class EnumLabel
{
    /**
     * Localized label for an enum value using the `enums.{translationKey}.{value}` translation,
     * falling back to a headline of the raw value when no translation exists.
     */
    public static function for(BackedEnum|string|int|null $value, string $translationKey): ?string
    {
        if ($value === null) {
            return null;
        }

        $raw = (string) ($value instanceof BackedEnum ? $value->value : $value);
        $key = "enums.{$translationKey}.{$raw}";
        $label = __($key);

        return $label === $key ? Str::headline($raw) : $label;
    }
}
