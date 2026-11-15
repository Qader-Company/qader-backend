<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class WebsiteSettings extends Settings
{
    public ?string $footer_copyright_text_ar;
    public ?string $footer_copyright_text_en;

    public static function group(): string
    {
        return 'website';
    }
}
