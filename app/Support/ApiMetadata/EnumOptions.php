<?php

namespace App\Support\ApiMetadata;

use BackedEnum;
use Illuminate\Support\Str;

class EnumOptions
{
    /**
     * @param  class-string<BackedEnum>  $enumClass
     * @return array<int, array{value: string|int, label: string}>
     */
    public static function make(string $enumClass, string $translationKey): array
    {
        return array_map(
            static function (BackedEnum $case) use ($translationKey): array {
                $key = "enums.{$translationKey}.{$case->value}";
                $label = __($key);

                return [
                    'value' => $case->value,
                    'label' => $label === $key
                        ? Str::headline((string) $case->value)
                        : $label,
                ];
            },
            $enumClass::cases(),
        );
    }
}
