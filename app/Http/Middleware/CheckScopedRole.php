<?php

namespace App\Http\Middleware;

use App\Modules\V1\Users\Application\Services\UserAccessResolver;
use App\Modules\V1\Users\Domain\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CheckScopedRole
{
    public function handle(Request $request, Closure $next, string $roles, ?string $guard = null): mixed
    {
        $user = $request->user($guard);
        $required = array_filter(array_map('trim', explode('|', $roles)));

        if (
            ! $user instanceof User
            || ! UserAccessResolver::hasAnyRole($user, $required)
        ) {
            throw new AccessDeniedHttpException(__('auth.unauthorized'));
        }

        return $next($request);
    }
}
