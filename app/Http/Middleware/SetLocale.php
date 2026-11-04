<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    private const SUPPORTED_LOCALES = ['ar', 'en'];

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        App::setLocale($this->resolveLocale($request) ?? config('app.locale', 'en'));

        return $next($request);
    }

    private function resolveLocale(Request $request): ?string
    {
        foreach ($request->getLanguages() as $language) {
            $primary = strtolower(explode('-', str_replace('_', '-', $language), 2)[0]);
            if (in_array($primary, self::SUPPORTED_LOCALES, true)) {
                return $primary;
            }
        }

        return null;
    }
}
