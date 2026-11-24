<?php

namespace Tests\Feature;

use Tests\TestCase;

class BrowserRouteProtectionTest extends TestCase
{
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
}
