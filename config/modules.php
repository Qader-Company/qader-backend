<?php

use App\Modules\V1\AccessControl\Infrastructure\Providers\AccessControlModuleServiceProvider;
use App\Modules\V1\Admins\Infrastructure\Providers\AdminModuleServiceProvider;
use App\Modules\V1\Authentication\Infrastructure\Providers\AuthenticationModuleServiceProvider;
use App\Modules\V1\Cities\Infrastructure\Providers\CityModuleServiceProvider;
use App\Modules\V1\Countries\Infrastructure\Providers\CountryModuleServiceProvider;
use App\Modules\V1\Currencies\Infrastructure\Providers\CurrencyModuleServiceProvider;
use App\Modules\V1\JobTitles\Infrastructure\Providers\JobTitleModuleServiceProvider;
use App\Modules\V1\Languages\Infrastructure\Providers\LanguageModuleServiceProvider;
use App\Modules\V1\ProfessionalFields\Infrastructure\Providers\ProfessionalFieldModuleServiceProvider;
use App\Modules\V1\Skills\Infrastructure\Providers\SkillModuleServiceProvider;
use App\Modules\V1\TalentApplications\Infrastructure\Providers\TalentApplicationModuleServiceProvider;
use App\Modules\V1\Talents\Infrastructure\Providers\TalentModuleServiceProvider;
use App\Modules\V1\Tools\Infrastructure\Providers\ToolModuleServiceProvider;
use App\Modules\V1\Users\Infrastructure\Providers\UserModuleServiceProvider;

return [
    'routes' => [
        'public' => [
            ['prefix' => 'auth', 'file' => 'auth.php'],
        ],

        'admin' => [
            ['prefix' => 'admin/access-control', 'file' => 'access-control.php'],
            ['prefix' => 'admin/admins', 'file' => 'admins.php'],
            ['prefix' => 'admin/countries', 'file' => 'countries.php'],
            ['prefix' => 'admin/cities', 'file' => 'cities.php'],
            ['prefix' => 'admin/languages', 'file' => 'languages.php'],
            ['prefix' => 'admin/fields', 'file' => 'fields.php'],
            ['prefix' => 'admin/job-titles', 'file' => 'job-titles.php'],
            ['prefix' => 'admin/skills', 'file' => 'skills.php'],
            ['prefix' => 'admin/tools', 'file' => 'tools.php'],
            ['prefix' => 'admin/currencies', 'file' => 'currencies.php'],
        ],

        'client' => [
            //            ['prefix' => '', 'file' => '', 'middlewares' => ''],
        ],

        'talent' => [
            [
                'prefix' => 'talent/master-data',
                'file' => 'master-data.php',
                'middlewares' => ['auth:sanctum', 'abilities:access,talent'],
            ],
            [
                'prefix' => 'talent/profile',
                'file' => 'profile.php',
                'middlewares' => ['auth:sanctum', 'abilities:access,talent'],
            ],
            [
                'prefix' => 'talent/onboarding',
                'file' => 'onboarding.php',
                'middlewares' => ['auth:sanctum', 'abilities:access,talent'],
            ],
        ],
    ],

    'providers' => [
        AccessControlModuleServiceProvider::class,
        AdminModuleServiceProvider::class,
        AuthenticationModuleServiceProvider::class,
        CityModuleServiceProvider::class,
        CountryModuleServiceProvider::class,
        CurrencyModuleServiceProvider::class,
        JobTitleModuleServiceProvider::class,
        LanguageModuleServiceProvider::class,
        ProfessionalFieldModuleServiceProvider::class,
        SkillModuleServiceProvider::class,
        TalentApplicationModuleServiceProvider::class,
        TalentModuleServiceProvider::class,
        ToolModuleServiceProvider::class,
        UserModuleServiceProvider::class,
    ],
];
