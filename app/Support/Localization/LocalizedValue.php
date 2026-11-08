<?php

namespace App\Support\Localization;

final class LocalizedValue
{
    public static function resolve(mixed $english, mixed $arabic): mixed
    {
        $preferred = self::isArabic() ? $arabic : $english;
        $fallback = self::isArabic() ? $english : $arabic;

        return self::hasValue($preferred) ? $preferred : $fallback;
    }

    public static function preferredColumn(string $english, string $arabic): string
    {
        return self::isArabic() ? $arabic : $english;
    }

    public static function coalescedColumn(string $english, string $arabic, string $alias): string
    {
        $preferred = self::preferredColumn($english, $arabic);
        $fallback = self::preferredColumn($arabic, $english);

        return "COALESCE(NULLIF({$preferred}, ''), {$fallback}) as {$alias}";
    }

    private static function isArabic(): bool
    {
        return app()->getLocale() === 'ar';
    }

    private static function hasValue(mixed $value): bool
    {
        return $value !== null && (! is_string($value) || $value !== '');
    }
}
