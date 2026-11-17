<?php

namespace App\Support\Http\Resources;

final class AdminFormInput
{
    public static function make(string $key, mixed $value, string $type, ?string $label = null): array
    {
        $input = [
            'key' => $key,
        ];

        if ($label !== null) {
            $input['label'] = $label;
        }

        return $input + [
            'value' => $value,
            'type' => $type,
        ];
    }
}
