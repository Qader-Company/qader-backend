<?php

namespace App\Support\ApiMetadata;

use BackedEnum;

class EnumOptions
{
    /**
     * @param  class-string<BackedEnum>  $enumClass
     * @return array<int, array{value: string|int, label: string}>
     */
    public static function make(string $enumClass, string $translationKey): array
    {
        return array_map(
            static fn (BackedEnum $case): array => [
                'value' => $case->value,
                'label' => EnumLabel::for($case, $translationKey),
            ],
            $enumClass::cases(),
        );
    }
}
