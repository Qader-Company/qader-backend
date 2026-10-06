<?php

namespace App\Modules\V1\Talents\Application\UseCases;

use BackedEnum;

class ListTalentEnumOptionsUseCase
{
    /**
     * @param  class-string<BackedEnum>  $enumClass
     */
    public function execute(string $enumClass, string $translationKey): array
    {
        return array_map(
            fn (BackedEnum $option): array => [
                'value' => $option->value,
                'label' => __("enums.{$translationKey}.{$option->value}"),
            ],
            $enumClass::cases(),
        );
    }
}
