<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RejectBrowserApiRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        if (
            ! $request->isMethod('OPTIONS')
            && $request->acceptsHtml()
            && ! $request->expectsJson()
        ) {
            throw new NotFoundHttpException;
        }

        return $next($request);
    }
}
