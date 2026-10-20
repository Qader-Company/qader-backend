<?php

use App\Support\ApiDocumentation\ApidogFolders;
use App\Support\ApiDocumentation\DocumentationBodyParameters;
use App\Support\ApiDocumentation\DocumentationQueryParameters;
use App\Support\ApiDocumentation\DocumentationUrlParameters;
use App\Support\ApiDocumentation\PortalMetadata;
use App\Support\ApiDocumentation\PortalRouteMatcher;
use Knuckles\Scribe\Extracting\Strategies;

// Keep this file loadable without require-dev packages (including config:cache).
// Unspecified settings are merged from Scribe only when its local provider boots.
return [
    'title' => 'Qader API',
    'description' => 'Local API reference for the implemented Qader modules.',
    'base_url' => config('app.url'),
    'routeMatcher' => PortalRouteMatcher::class,
    'groups' => [
        'default' => 'talent',
        'order' => ['talent', 'client', 'admin'],
    ],

    'routes' => [
        [
            'match' => [
                'prefixes' => ['api/v1/*'],
                'domains' => ['*'],
            ],
            'include' => [],
            'exclude' => [],
        ],
    ],

    // Serve through Laravel; never generate publicly accessible static docs.
    'type' => 'laravel',
    // Scribe moves its temporary assets into vendor/scribe. Keep both paths
    // on the shared assets volume when generating inside local Docker.
    'static' => [
        'output_path' => 'public/vendor/.scribe-tmp',
    ],
    'laravel' => [
        'add_routes' => env('APP_ENV') === 'local',
        'docs_url' => '/docs',
        'assets_directory' => null,
        'middleware' => [],
    ],

    'auth' => [
        'enabled' => true,
        'default' => false,
        'in' => 'bearer',
        'name' => 'Authorization',
        'use_value' => null,
        'placeholder' => '{{access_token}}',
        'extra_info' => 'Use the appropriate Sanctum token for authenticated endpoints.',
    ],

    'postman' => [
        'enabled' => true,
        'overrides' => [],
    ],
    'openapi' => [
        'enabled' => true,
        'version' => '3.0.3',
        'overrides' => [],
        'generators' => [ApidogFolders::class],
    ],

    'examples' => [
        'faker_seed' => 1234,
        'models_source' => ['factoryMake'],
    ],

    // Class-name strings remain valid even when Scribe is not installed.
    // Response examples come from documentation, without calling API endpoints.
    'strategies' => [
        'metadata' => [
            Strategies\Metadata\GetFromDocBlocks::class,
            Strategies\Metadata\GetFromMetadataAttributes::class,
            PortalMetadata::class,
        ],
        'headers' => [
            [
                Strategies\StaticData::class,
                [
                    'data' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'X-Authorization' => '{{api_key}}',
                    ],
                ],
            ],
            Strategies\Headers\GetFromHeaderAttribute::class,
            Strategies\Headers\GetFromHeaderTag::class,
        ],
        'urlParameters' => [
            DocumentationUrlParameters::class,
            Strategies\UrlParameters\GetFromUrlParamAttribute::class,
            Strategies\UrlParameters\GetFromUrlParamTag::class,
        ],
        'queryParameters' => [
            DocumentationQueryParameters::class,
            Strategies\QueryParameters\GetFromInlineValidator::class,
            Strategies\QueryParameters\GetFromQueryParamAttribute::class,
            Strategies\QueryParameters\GetFromQueryParamTag::class,
        ],
        'bodyParameters' => [
            DocumentationBodyParameters::class,
            Strategies\BodyParameters\GetFromInlineValidator::class,
            Strategies\BodyParameters\GetFromBodyParamAttribute::class,
            Strategies\BodyParameters\GetFromBodyParamTag::class,
        ],
        'responses' => [
            Strategies\Responses\UseResponseAttributes::class,
            Strategies\Responses\UseResponseTag::class,
            Strategies\Responses\UseResponseFileTag::class,
        ],
        'responseFields' => [
            Strategies\ResponseFields\GetFromResponseFieldAttribute::class,
            Strategies\ResponseFields\GetFromResponseFieldTag::class,
        ],
    ],
];
