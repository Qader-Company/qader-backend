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
                $export['paths'] = [];

                foreach ($spec['paths'] as $uri => $pathItem) {
                    $operations = [];

                    foreach (['get', 'post', 'put', 'patch', 'delete', 'options', 'head'] as $method) {
                        if (! isset($pathItem[$method])) {
                            continue;
                        }

                        $operation = $pathItem[$method];
                        $sharedLogout = $uri === '/api/v1/auth/logout' && $method === 'delete';

                        if (! $sharedLogout && ! str_starts_with($operation['x-apidog-folder'] ?? '', $portal.'/')) {
                            continue;
                        }

                        if ($sharedLogout) {
                            $operation['tags'] = [$portal];
                            $operation['x-apidog-folder'] = $portal.'/auth';
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
            $logout = null;

            foreach ($collection['item'] as $group) {
                foreach ($group['item'] as $module) {
                    foreach ($module['item'] ?? [] as $item) {
                        if (self::isLogout($item)) {
                            $logout = $item;
                        }
                    }
                }
            }

            if ($logout) {
                foreach ($collection['item'] as &$group) {
                    foreach ($group['item'] as &$module) {
                        if ($group['name'] !== 'talent' && $module['name'] === 'auth'
                            && ! array_filter($module['item'], self::isLogout(...))) {
                            $module['item'][] = $logout;
                        }
                    }
                    unset($module);
                }
                unset($group);
            }

            file_put_contents($paths['postman'], json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        }
    }

    private static function isLogout(array $item): bool
    {
        $path = $item['request']['url']['path'] ?? '';
        $path = is_array($path) ? implode('/', $path) : $path;

        return ($item['request']['method'] ?? '') === 'DELETE'
            && ltrim($path, '/') === 'api/v1/auth/logout';
    }
}
