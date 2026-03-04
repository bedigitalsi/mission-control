<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Allow bearer token auth (external API calls)
        $token = $request->bearerToken();
        if ($token && $token === config('app.api_token')) {
            return $next($request);
        }

        // Allow session auth (frontend calls from logged-in user)
        if (Auth::check()) {
            return $next($request);
        }

        return response()->json(['error' => 'Unauthorized'], 401);
    }
}
