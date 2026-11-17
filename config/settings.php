<?php

use App\Settings\HiringSettings;
use App\Settings\PaidServicesSettings;
use App\Settings\PlatformSettings;
use App\Settings\TalentEvaluationSettings;
use App\Settings\TrashSettings;
use App\Settings\WebsiteSettings;

return [
    'settings' => [
        HiringSettings::class,
        TalentEvaluationSettings::class,
        TrashSettings::class,
        PlatformSettings::class,
        WebsiteSettings::class,
        PaidServicesSettings::class,
    ],
    'setting_class_path' => app_path('Settings'),
    'migrations_paths' => [database_path('settings')],
    'default_repository' => 'database',
    'repositories' => [
        'database' => [
            'type' => Spatie\LaravelSettings\SettingsRepositories\DatabaseSettingsRepository::class,
            'model' => null,
            'table' => null,
            'connection' => null,
        ],
        'redis' => [
            'type' => Spatie\LaravelSettings\SettingsRepositories\RedisSettingsRepository::class,
            'connection' => null,
            'prefix' => null,
        ],
    ],
    'encoder' => null,
    'decoder' => null,
    'cache' => [
        'enabled' => (bool) env('SETTINGS_CACHE_ENABLED', false),
        'store' => null,
        'prefix' => 'qader_settings',
        'ttl' => null,
        'memo' => env('SETTINGS_CACHE_MEMO', false),
    ],
    'global_casts' => [
        DateTimeInterface::class => Spatie\LaravelSettings\SettingsCasts\DateTimeInterfaceCast::class,
        DateTimeZone::class => Spatie\LaravelSettings\SettingsCasts\DateTimeZoneCast::class,
    ],
    'auto_discover_settings' => [],
    'discovered_settings_cache_path' => base_path('bootstrap/cache'),
];
