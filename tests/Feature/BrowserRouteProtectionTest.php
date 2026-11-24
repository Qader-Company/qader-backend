<?php

namespace Tests\Feature;

use App\Http\Middleware\RejectBrowserApiRequests;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class BrowserRouteProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_browser_navigation_to_an_existing_api_route_returns_the_html_not_found_page(): void
    {
        $this->get('/api/v1/public/website', ['Accept' => 'text/html'])
            ->assertNotFound()
            ->assertViewIs('errors.404')
            ->assertSee('Page not found.');
    }

    public function test_json_requests_are_not_blocked_by_browser_route_protection(): void
    {
        $this->getJson('/api/v1/public/website')
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_unknown_browser_route_uses_the_html_not_found_page(): void
    {
        $this->get('/missing-page', ['Accept' => 'text/html'])
            ->assertNotFound()
            ->assertViewIs('errors.404');
    }

    public function test_maintenance_commands_are_not_blocked_by_browser_route_protection(): void
    {
        $request = Request::create('/api/maintenance/migrate', 'POST', server: [
            'HTTP_ACCEPT' => 'text/html',
        ]);

        $response = (new RejectBrowserApiRequests)->handle(
            $request,
            fn () => response()->json(['reached' => true]),
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['reached' => true], $response->getData(true));
    }
}
