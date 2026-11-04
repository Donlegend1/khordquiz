<?php

namespace App\Http\Middleware;

use App\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Switches the app locale to the signed-in member's chosen language
 * (users.language). Guests and members who never picked one get English.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale(Locales::codeFor($request->user()?->language));

        return $next($request);
    }
}
