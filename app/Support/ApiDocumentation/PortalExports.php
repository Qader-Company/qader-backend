<?php

namespace App\Support\ApiDocumentation;

use Symfony\Component\Yaml\Yaml;

class PortalExports
{
    public static function write(array $paths): void
    {
        if (! empty($paths['openapi'])) {
            $spec = Yaml::parseFile($paths['openapi']);

            foreach (PortalRouteMatcher::PORTALS as $portal) {
                $export = $spec;
                $export['info']['title'] = 'Qader '.$portal.' API';
                $export['tags'] = array_values(array_filter($spec['tags'] ?? [], fn ($tag) => $tag['name'] === $portal));
                unset($export['components']['securitySchemes'], $export['security']);
                $export['paths'] = [];

                foreach ($spec['paths'] as $uri => $pathItem) {
                    $operations = [];

                    foreach (['get', 'post', 'put', 'patch', 'delete', 'options', 'head'] as $method) {
                        if (! isset($pathItem[$method])) {
                            continue;
                        }

                        $operation = $pathItem[$method];
                        $sharedAuth = self::isSharedAuthOperation($uri, $method);

                        if (! $sharedAuth && ! str_starts_with($operation['x-apidog-folder'] ?? '', $portal.'/')) {
                            continue;
                        }

                        if ($sharedAuth) {
                            $operation['tags'] = [$portal];
                            $operation['x-apidog-folder'] = $portal.'/auth';
                        }

                        $operation = self::withAuthenticationVariables($uri, $operation);
                        $operation = self::withStableRequestExamples($uri, $method, $operation);

                        if (empty($operation['responses'])) {
                            $operation['responses'] = [
                                'default' => [
                                    'description' => 'Response depends on the operation outcome.',
                                ],
                            ];
                        }

                        $operations[$method] = $operation;
                    }

                    if ($operations) {
                        // Preserve path-level parameters and servers, but not other portals' operations.
                        $export['paths'][$uri] = array_diff_key($pathItem, array_flip(['get', 'post', 'put', 'patch', 'delete', 'options', 'head'])) + $operations;
                    }
                }

                file_put_contents(dirname($paths['openapi']).'/'.$portal.'.openapi.yaml', Yaml::dump($export, 20, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE | Yaml::DUMP_OBJECT_AS_MAP));
            }
        }

        if (! empty($paths['postman'])) {
            $collection = json_decode(file_get_contents($paths['postman']), true, flags: JSON_THROW_ON_ERROR);
            $sharedAuthItems = [];

            foreach ($collection['item'] as $group) {
                foreach ($group['item'] as $module) {
                    foreach ($module['item'] ?? [] as $item) {
                        if (self::isSharedAuthItem($item)) {
                            $sharedAuthItems[] = $item;
                        }
                    }
                }
            }

            if ($sharedAuthItems) {
                foreach ($collection['item'] as &$group) {
                    foreach ($group['item'] as &$module) {
                        if ($group['name'] === 'talent' || $module['name'] !== 'auth') {
                            continue;
                        }

                        foreach ($sharedAuthItems as $sharedAuthItem) {
                            if (! array_filter(
                                $module['item'],
                                fn (array $item): bool => self::sameOperation($item, $sharedAuthItem)
                            )) {
                                $module['item'][] = $sharedAuthItem;
                            }
                        }
                    }
                    unset($module);
                }
                unset($group);
            }

            self::normalizePostmanExamples($collection);
            self::normalizePostmanAuthentication($collection);

            file_put_contents($paths['postman'], json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        }
    }

    private static function withAuthenticationVariables(string $uri, array $operation): array
    {
        $operation['parameters'] ??= [];
        self::upsertOpenApiHeader($operation['parameters'], 'Accept', 'application/json');

        foreach ($operation['parameters'] ?? [] as &$parameter) {
            if (($parameter['in'] ?? null) !== 'header' || strcasecmp($parameter['name'] ?? '', 'X-Authorization') !== 0) {
                continue;
            }

            $parameter['example'] = '{{api_key}}';
            $parameter['schema']['example'] = '{{api_key}}';
        }
        unset($parameter);

        $tokenVariable = self::tokenVariableFor($uri, $operation);

        if ($tokenVariable === null) {
            $operation['security'] = [];

            return $operation;
        }

        // OpenAPI security schemes intentionally do not carry credential values.
        // Apidog therefore replaces every imported Bearer value with its own
        // {{bearerToken}} variable. Keep auth explicit per operation so the
        // imported request uses the correct Qader token variable.
        $operation['security'] = [];
        $authorization = 'Bearer {{'.$tokenVariable.'}}';
        $operation['parameters'][] = [
            'in' => 'header',
            'name' => 'Authorization',
            'description' => 'Bearer token populated from the Apidog '.$tokenVariable.' variable.',
            'required' => true,
            'example' => $authorization,
            'schema' => [
                'type' => 'string',
                'example' => $authorization,
            ],
        ];

        return $operation;
    }

    private static function upsertOpenApiHeader(array &$parameters, string $name, string $value): void
    {
        foreach ($parameters as &$parameter) {
            if (($parameter['in'] ?? null) !== 'header' || strcasecmp($parameter['name'] ?? '', $name) !== 0) {
                continue;
            }

            $parameter['required'] = true;
            $parameter['example'] = $value;
            $parameter['schema'] = [
                'type' => 'string',
                'example' => $value,
            ];

            return;
        }
        unset($parameter);

        $parameters[] = [
            'in' => 'header',
            'name' => $name,
            'required' => true,
            'example' => $value,
            'schema' => [
                'type' => 'string',
                'example' => $value,
            ],
        ];
    }

    private static function tokenVariableFor(string $uri, array $operation): ?string
    {
        if (($operation['security'] ?? null) === []) {
            return null;
        }

        return match (true) {
            str_ends_with($uri, '/email-verification') => 'verification_token',
            str_ends_with($uri, '/refresh') => 'refresh_token',
            str_ends_with($uri, '/reset-password') => 'reset_password_token',
            default => 'access_token',
        };
    }

    private static function isSharedAuthOperation(string $uri, string $method): bool
    {
        return ($uri === '/api/v1/auth/logout' && $method === 'delete')
            || ($uri === '/api/v1/auth/me' && $method === 'get');
    }

    private static function isSharedAuthItem(array $item): bool
    {
        $path = $item['request']['url']['path'] ?? '';
        $path = is_array($path) ? implode('/', $path) : $path;
        $method = $item['request']['method'] ?? '';

        return self::isSharedAuthOperation('/'.ltrim($path, '/'), strtolower($method));
    }

    private static function sameOperation(array $first, array $second): bool
    {
        $firstPath = $first['request']['url']['path'] ?? '';
        $secondPath = $second['request']['url']['path'] ?? '';

        return ($first['request']['method'] ?? null) === ($second['request']['method'] ?? null)
            && $firstPath === $secondPath;
    }

    private static function withStableRequestExamples(string $uri, string $method, array $operation): array
    {
        if ($uri !== '/api/v1/admin/talent-applications/{application}/reject' || $method !== 'post') {
            return $operation;
        }

        $content = &$operation['requestBody']['content']['application/json'];
        $allowedAfterPeriod = [
            'reapply_policy' => 'allowed_after_period',
            'reapply_after_months' => 6,
        ];
        $blacklisted = [
            'reapply_policy' => 'blacklisted',
            'blacklist_reason' => 'Repeated submission of invalid information.',
        ];
        unset($content['example']);
        $content['schema']['example'] = $allowedAfterPeriod;
        $content['examples'] = [
            'allowed_after_period' => [
                'summary' => 'Allow another application after a waiting period',
                'value' => $allowedAfterPeriod,
            ],
            'blacklisted' => [
                'summary' => 'Prevent the talent from applying again',
                'value' => $blacklisted,
            ],
        ];

        return $operation;
    }

    private static function normalizePostmanExamples(array &$collection): void
    {
        foreach ($collection['item'] as &$group) {
            if (! isset($group['item'])) {
                continue;
            }

            foreach ($group['item'] as &$module) {
                if (! isset($module['item'])) {
                    continue;
                }

                foreach ($module['item'] as &$item) {
                    $path = $item['request']['url']['path'] ?? [];
                    $path = is_array($path) ? '/'.implode('/', $path) : '/'.ltrim($path, '/');

                    if (
                        in_array($path, [
                            '/api/v1/admin/talent-applications/{application}/reject',
                            '/api/v1/admin/talent-applications/:application/reject',
                        ], true)
                        && ($item['request']['method'] ?? '') === 'POST'
                    ) {
                        $item['request']['body']['raw'] = json_encode([
                            'reapply_policy' => 'allowed_after_period',
                            'reapply_after_months' => 6,
                        ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
                    }
                }
                unset($item);
            }
            unset($module);
        }
        unset($group);
    }

    private static function normalizePostmanAuthentication(array &$collection): void
    {
        $variables = ['api_key', 'access_token', 'verification_token', 'refresh_token', 'reset_password_token'];
        $existingVariables = array_column($collection['variable'] ?? [], 'key');

        foreach ($variables as $variable) {
            if (! in_array($variable, $existingVariables, true)) {
                $collection['variable'][] = [
                    'key' => $variable,
                    'value' => '',
                    'type' => 'string',
                ];
            }
        }

        self::walkPostmanItems($collection['item']);
    }

    private static function walkPostmanItems(array &$items): void
    {
        foreach ($items as &$item) {
            if (isset($item['item'])) {
                self::walkPostmanItems($item['item']);

                continue;
            }

            $request = &$item['request'];
            $path = $request['url']['path'] ?? [];
            $uri = is_array($path) ? '/'.implode('/', $path) : '/'.ltrim($path, '/');

            foreach ($request['header'] ?? [] as &$header) {
                if (strcasecmp($header['key'] ?? '', 'X-Authorization') === 0) {
                    $header['value'] = '{{api_key}}';
                }
            }
            unset($header);

            self::upsertPostmanHeader($request, 'Accept', 'application/json');

            $tokenVariable = self::postmanTokenVariableFor($uri, $request);
            $request['auth'] = $tokenVariable === null
                ? ['type' => 'noauth']
                : [
                    'type' => 'bearer',
                    'bearer' => [[
                        'key' => 'token',
                        'value' => '{{'.$tokenVariable.'}}',
                        'type' => 'string',
                    ]],
                ];
        }
        unset($item);
    }

    private static function upsertPostmanHeader(array &$request, string $key, string $value): void
    {
        $request['header'] ??= [];

        foreach ($request['header'] as &$header) {
            if (strcasecmp($header['key'] ?? '', $key) !== 0) {
                continue;
            }

            $header['value'] = $value;
            $header['type'] = 'text';
            unset($header['disabled']);

            return;
        }
        unset($header);

        $request['header'][] = [
            'key' => $key,
            'value' => $value,
            'type' => 'text',
        ];
    }

    private static function postmanTokenVariableFor(string $uri, array $request): ?string
    {
        if (($request['auth']['type'] ?? null) === 'noauth') {
            return null;
        }

        return match (true) {
            str_ends_with($uri, '/email-verification') => 'verification_token',
            str_ends_with($uri, '/refresh') => 'refresh_token',
            str_ends_with($uri, '/reset-password') => 'reset_password_token',
            default => 'access_token',
        };
    }
}
