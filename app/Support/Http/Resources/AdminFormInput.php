<?php

namespace App\Support\Http\Resources;

final class AdminFormInput
{
    public static function make(string $key, mixed $value, string $type): array
    {
        return [
            'key' => $key,
            'value' => $value,
            'type' => $type,
        ];
    }
}
