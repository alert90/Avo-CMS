<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Closure;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'api/*',
        'api/auth/*',
        'api/auth/login',
        'api/auth/register',
        'api/auth/logout',
        'api/auth/refresh',
        'sanctum/csrf-cookie',
        'login',
        'logout',
        'register',
        '*',
    ];

    /**
     * Handle an incoming request - completely bypass CSRF for API routes
     */
    public function handle($request, Closure $next)
    {
        // Skip CSRF verification entirely for API routes
        if ($request->is('api/*') || $request->is('api/**')) {
            return $next($request);
        }

        // Skip if request has Origin header (CORS request)
        if ($request->headers->has('Origin')) {
            return $next($request);
        }

        // Skip for all requests with Accept: application/json
        if ($request->expectsJson()) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }
}
