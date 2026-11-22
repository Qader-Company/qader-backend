<?php

namespace App\Http\Middleware;

use App\Modules\V1\Notifications\Application\Support\NotificationMessage;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stores the language of the authenticated user's latest request, so pushes and emails sent
 * later (outside any request) use it. Runs after the request, once auth has resolved the user.
 */
class RememberUserLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();
        $locale = App::getLocale();
        if ($user !== null
            && $request->headers->has('Accept-Language')
            && in_array($locale, NotificationMessage::SUPPORTED_LOCALES, true)
            && $user->getAttribute('locale') !== $locale) {
            // Query builder update: no model events and no `updated_at` change.
            DB::table($user->getTable())->where('id', $user->getKey())->update(['locale' => $locale]);
            $user->setAttribute('locale', $locale);
            $user->syncOriginalAttribute('locale');
        }

        return $response;
    }
}
