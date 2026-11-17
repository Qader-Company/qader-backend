<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class PlatformSettings extends Settings
{
    public int $support_response_business_days;
    public ?string $support_email;
    public ?string $support_phone;
    public ?string $support_whatsapp;
    public ?string $privacy_policy_url;
    public ?string $terms_and_conditions_url;

    public static function group(): string
    {
        return 'platform';
    }
}
