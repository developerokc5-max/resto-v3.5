<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireAuth
{
    // Routes that don't need login
    protected array $except = [
        '/login',
        '/auth/google',
        '/auth/google/callback',
        '/auth/totp/setup',
        '/auth/totp/setup/confirm',
        '/auth/totp/verify',
        '/auth/totp/check',
        '/offline',
        '/up',
    ];

    public function handle(Request $request, Closure $next)
    {
        // Auth disabled via env — bypass entirely
        if (!config('app.auth_enabled', true)) {
            return $next($request);
        }

        // Always allow API routes (scrapers, GitHub Actions)
        if ($request->is('api/*')) {
            return $next($request);
        }

        // Allow whitelisted paths
        foreach ($this->except as $path) {
            if ($request->is(ltrim($path, '/'))) {
                return $next($request);
            }
        }

        // Must have completed both Google OAuth + TOTP
        if (!session('auth_user')) {
            return redirect('/login');
        }

        return $next($request);
    }
}
