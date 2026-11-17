<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class TrashSettings extends Settings
{
    public int $retention_days;

    public static function group(): string
    {
        return 'trash';
    }
}
