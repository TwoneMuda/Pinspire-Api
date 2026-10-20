<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolve a Sanctum token when one is present on a public API route.
 *
 * Public endpoints such as /pins and /search still need the viewer to build
 * `is_liked`, `is_saved`, `is_following`, and private board responses. The
 * default guard is the session guard, so without this middleware a Bearer
 * token would be ignored on routes that do not require authentication.
 */
class UseSanctumWhenAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->bearerToken() !== null && Auth::guard('sanctum')->user() !== null) {
            Auth::shouldUse('sanctum');
        }

        return $next($request);
    }
}
