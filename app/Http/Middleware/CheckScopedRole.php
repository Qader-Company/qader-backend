<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CheckScopedRole
{
    public function handle(Request $request, Closure $next, string $roles, ?string $guard = null): mixed
    {
        $user = $request->user($guard);
        $required = array_filter(array_map('trim', explode('|', $roles)));

        if (! $user || ! $user->hasAnyRole($required)) {
            throw new AccessDeniedHttpException(__('auth.unauthorized'));
        }

        return $next($request);
    }
}
