<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class MarketplaceSettings extends Settings
{
    public ?string $anonymous_talent_image;

    public static function group(): string
    {
        return 'marketplace';
    }
}
