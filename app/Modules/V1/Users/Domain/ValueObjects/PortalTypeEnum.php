<?php

namespace App\Modules\V1\Users\Domain\ValueObjects;

enum PortalTypeEnum: string
{
    case TALENT = 'talent';
    case CLIENT = 'client';
    case ADMIN = 'admin';

    public static function values(): array
    {
        return array_map(
            fn (self $case): string => $case->value,
            self::cases()
        );
    }

    public static function getTypes(): array
    {
        return array_map(
            fn (self $item) => [
                'value' => $item->value,
            ],
            self::cases()
        );
    }
}
