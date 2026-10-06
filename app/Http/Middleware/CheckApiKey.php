<?php

namespace App\Http\Middleware;

use App\Facades\ApiResponse;
use Closure;
use Ejarnutowski\LaravelApiKey\Models\ApiKey;
use Ejarnutowski\LaravelApiKey\Models\ApiKeyAccessEvent;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckApiKey
{
    const AUTH_HEADER = 'X-Authorization';

    /**
     * Handle the incoming request
     *
     * @return ResponseFactory|mixed|Response
     */
    public function handle(Request $request, Closure $next)
    {
        $header = $request->header(self::AUTH_HEADER);
        $apiKey = ApiKey::getByKey($header);
        if ($apiKey instanceof ApiKey) {
            $this->logAccessEvent($request, $apiKey);

            return $next($request);
        }

        return ApiResponse::unauthorized();
    }

    /**
     * Log an API key access event
     */
    protected function logAccessEvent(Request $request, ApiKey $apiKey)
    {
        $event = new ApiKeyAccessEvent;
        $event->api_key_id = $apiKey->id;
        $event->ip_address = $request->ip();
        $event->url = $request->fullUrl();
        $event->save();
    }
}
