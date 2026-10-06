<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CheckScopedPermission
{
    public function handle(Request $request, Closure $next, string $permissions, ?string $guard = null): mixed
    {
        $user = $request->user($guard);
        $required = array_filter(array_map('trim', explode('|', $permissions)));

        if (! $user || ! $user->hasAnyPermission($required)) {
            throw new AccessDeniedHttpException(__('auth.unauthorized'));
        }

        return $next($request);
    }
}
