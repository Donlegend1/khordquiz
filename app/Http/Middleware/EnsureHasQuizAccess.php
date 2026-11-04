<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Playing quizzes needs a plan — a trial, a paid plan (Stripe, app stores),
 * a Kingsleykhordpiano membership, or an admin-granted plan — same rule the
 * mobile app applies via Subscription::unlocksQuizzes(). Browsing the catalog
 * and quiz previews stays open.
 */
class EnsureHasQuizAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->hasQuizAccess()) {
            return $next($request);
        }

        return redirect()->route('subscription', ['locked' => 1]);
    }
}
