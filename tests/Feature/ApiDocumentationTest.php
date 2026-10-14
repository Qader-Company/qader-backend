<?php

namespace Tests\Feature;

use App\Providers\LocalApiDocumentationServiceProvider;
use App\Support\ApiDocumentation\PortalExports;
use App\Support\ApiDocumentation\PortalRouteMatcher;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Knuckles\Camel\Output\OutputEndpointData;
use Knuckles\Scribe\Extracting\Extractor;
use Knuckles\Scribe\Tools\DocumentationConfig;
use Knuckles\Scribe\Writing\OpenAPISpecWriter;
use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

class ApiDocumentationTest extends TestCase
{
    public function test_generated_openapi_has_named_requests_with_bodies_and_query_parameters(): void
    {
        $this->app->register(LocalApiDocumentationServiceProvider::class);
        $config = new DocumentationConfig(config('scribe'));
        $extractor = new Extractor($config);
        $selected = [
            'POST api/v1/auth/talent/register',
            'POST api/v1/auth/talent/login',
            'GET api/v1/talent/master-data/cities',
            'GET api/v1/admin/countries',
            'PATCH api/v1/admin/countries/{country}',
            'PATCH api/v1/talent/profile',
            'POST api/v1/talent/onboarding/personal-information',
            'POST api/v1/talent/onboarding/skills-and-experiences',
            'POST api/v1/talent/onboarding/work-preferences',
        ];
        $groups = [];

        foreach ((new PortalRouteMatcher)->getRoutes(config('scribe.routes')) as $match) {
            $route = $match->getRoute();
            if (! in_array($route->methods()[0].' '.$route->uri(), $selected, true)) {
                continue;
            }
            $endpoint = $extractor->processRoute($route);
            $portal = $endpoint->metadata->groupName;
            $groups[$portal] ??= ['name' => $portal, 'description' => '', 'endpoints' => []];
            $groups[$portal]['endpoints'][] = new OutputEndpointData($endpoint->toArray());

            if ($route->uri() === 'api/v1/auth/talent/register') {
                $this->assertSame($endpoint->bodyParameters['password']->example, $endpoint->bodyParameters['password_confirmation']->example);
            }
        }

        $spec = json_decode(json_encode((new OpenAPISpecWriter($config))->generateSpecContent(array_values($groups)), JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
        $this->assertCount(count($selected), $spec['paths']);
        $login = $spec['paths']['/api/v1/auth/talent/login']['post'];
        $this->assertSame('Login', $login['summary']);
        $this->assertSame('Login', $login['x-apidog-name']);
        $this->assertArrayHasKey('email', $login['requestBody']['content']['application/json']['schema']['properties']);
        $this->assertArrayHasKey('password', $login['requestBody']['content']['application/json']['schema']['properties']);
        $this->assertArrayHasKey('email', $login['requestBody']['content']['application/json']['example']);
        $this->assertArrayHasKey('default', $login['responses']);

        $countries = $spec['paths']['/api/v1/admin/countries']['get'];
        $queries = array_column(array_filter($countries['parameters'], fn ($parameter) => $parameter['in'] === 'query'), 'name');
        $this->assertContains('search', $queries);
        $this->assertContains('per_page', $queries);
        $this->assertArrayNotHasKey('requestBody', $countries);
        $this->assertSame('List Countries', $countries['summary']);
        $this->assertSame('country_id', $spec['paths']['/api/v1/admin/countries/{country_id}']['parameters'][0]['name']);

        $profile = $spec['paths']['/api/v1/talent/profile']['patch'];
        $schema = $profile['requestBody']['content']['multipart/form-data']['schema'];
        $this->assertArrayHasKey('full_name', $schema['properties']['personal-information']['properties']);
        foreach (['personal-information' => 'full_name', 'skills-and-experiences' => 'skill_ids', 'work-preferences' => 'cv'] as $step => $field) {
            $operation = $spec['paths']['/api/v1/talent/onboarding/'.$step]['post'];
            $content = $operation['requestBody']['content'];
            $properties = reset($content)['schema']['properties'];
            $this->assertArrayHasKey($field, $properties);
            $this->assertStringStartsWith('Save ', $operation['summary']);
        }
    }

    public function test_auth_documentation_respects_portals_without_changing_live_routes(): void
    {
        $before = array_map(fn ($route) => $route->uri(), Route::getRoutes()->getRoutes());
        $matches = (new PortalRouteMatcher)->getRoutes(config('scribe.routes'));
        $uris = array_map(fn ($match) => $match->getRoute()->uri(), $matches);

        foreach (PortalRouteMatcher::PORTALS as $portal) {
            $this->assertContains('api/v1/auth/'.$portal.'/login', $uris);
            $this->assertContains('api/v1/auth/'.$portal.'/refresh', $uris);
        }

        $this->assertContains('api/v1/auth/client/register', $uris);
        $this->assertContains('api/v1/auth/talent/register', $uris);
        $this->assertNotContains('api/v1/auth/admin/register', $uris);
        $this->assertNotContains('api/v1/auth/admin/social/{provider}/login', $uris);
        $this->assertSame($before, array_map(fn ($route) => $route->uri(), Route::getRoutes()->getRoutes()));
        $this->assertFalse(Route::has('scribe.portal.openapi'));
    }

    public function test_portal_exports_keep_modules_isolated_and_include_shared_logout(): void
    {
        $directory = sys_get_temp_dir().'/qader-docs-'.bin2hex(random_bytes(8));
        File::makeDirectory($directory);

        try {
            $spec = ['openapi' => '3.0.3', 'info' => ['title' => 'Qader', 'version' => '1'], 'tags' => [], 'paths' => []];
            $logout = ['request' => ['method' => 'DELETE', 'url' => ['path' => 'api/v1/auth/logout']]];
            $collection = ['item' => []];

            foreach (PortalRouteMatcher::PORTALS as $portal) {
                $spec['tags'][] = ['name' => $portal];
                $spec['paths']['/api/v1/'.$portal.'/example/{id}'] = [
                    'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                    'get' => ['tags' => [$portal], 'x-apidog-folder' => $portal.'/example', 'parameters' => [['in' => 'query', 'name' => 'search', 'schema' => ['type' => 'string']]]],
                    'post' => ['tags' => [$portal], 'x-apidog-folder' => $portal.'/example', 'requestBody' => ['content' => ['application/json' => ['schema' => ['type' => 'object', 'properties' => ['name' => ['type' => 'string']]]]]]],
                ];
                $collection['item'][] = ['name' => $portal, 'item' => [['name' => 'auth', 'item' => $portal === 'talent' ? [$logout] : []]]];
            }

            $spec['paths']['/api/v1/auth/logout']['delete'] = ['tags' => ['talent'], 'x-apidog-folder' => 'talent/auth'];
            $paths = ['openapi' => $directory.'/openapi.yaml', 'postman' => $directory.'/collection.json'];
            File::put($paths['openapi'], Yaml::dump($spec, 20));
            File::put($paths['postman'], json_encode($collection, JSON_THROW_ON_ERROR));
            PortalExports::write($paths);
            PortalExports::write($paths);

            foreach (PortalRouteMatcher::PORTALS as $portal) {
                $export = Yaml::parseFile($directory.'/'.$portal.'.openapi.yaml');
                $this->assertCount(2, $export['paths']);
                $this->assertSame($portal.'/auth', $export['paths']['/api/v1/auth/logout']['delete']['x-apidog-folder']);
                $this->assertArrayHasKey('/api/v1/'.$portal.'/example/{id}', $export['paths']);
                $this->assertCount(1, $export['paths']['/api/v1/'.$portal.'/example/{id}']['parameters']);
                $this->assertSame('search', $export['paths']['/api/v1/'.$portal.'/example/{id}']['get']['parameters'][0]['name']);
                $this->assertArrayHasKey('name', $export['paths']['/api/v1/'.$portal.'/example/{id}']['post']['requestBody']['content']['application/json']['schema']['properties']);
            }

            $export = json_decode(File::get($paths['postman']), true, flags: JSON_THROW_ON_ERROR);
            foreach ($export['item'] as $group) {
                $this->assertCount(1, $group['item'][0]['item']);
            }
        } finally {
            File::deleteDirectory($directory);
        }
    }
}
