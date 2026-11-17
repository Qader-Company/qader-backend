<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class WebsiteSettings extends Settings
{
    public ?string $footer_copyright_text_ar;
    public ?string $footer_copyright_text_en;
    public int $company_founded_year;
    public float $referral_commission_percentage;
    public ?string $company_location_ar;
    public ?string $company_location_en;

    public static function group(): string
    {
        return 'website';
    }
}
