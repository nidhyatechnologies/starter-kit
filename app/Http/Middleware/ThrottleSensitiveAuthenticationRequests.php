<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ThrottleSensitiveAuthenticationRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('post') || ! in_array($request->path(), ['register', 'forgot-password', 'email/verification-notification'], true)) {
            return $next($request);
        }

        $key = 'sensitive-auth:'.$request->path().'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            abort(429, 'Too many requests. Please try again in a minute.');
        }

        RateLimiter::hit($key, 60);

        return $next($request);
    }
}
