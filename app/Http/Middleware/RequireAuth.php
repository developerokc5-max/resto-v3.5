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
        '/offline',
        '/up',
    ];

    public function handle(Request $request, Closure $next)
    {
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

        // Check session
        if (!session('auth_user')) {
            return redirect('/login')->with('intended', $request->url());
        }

        return $next($request);
    }
}
