<?php

namespace Tests\Feature;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RegistrationRateLimitTest extends TestCase
{
    public function test_registration_has_its_own_throttle_message_and_high_test_limit(): void
    {
        $request = Request::create('/api/v1/auth/talent/register', 'POST');
        $limiter = RateLimiter::limiter('auth-register');
        $limit = $limiter($request);

        $this->assertInstanceOf(Limit::class, $limit);
        $this->assertSame(1000, $limit->maxAttempts);

        $response = ($limit->responseCallback)($request, ['Retry-After' => 42]);

        $this->assertSame(429, $response->getStatusCode());
        $this->assertSame('42', $response->headers->get('Retry-After'));
        $this->assertSame(
            __('auth.registration_throttle'),
            $response->getData(true)['message'],
        );
    }
}
