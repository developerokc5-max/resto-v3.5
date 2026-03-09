<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OptimizePerformance
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Disable query logging in production to save memory
        if (app()->environment('production')) {
            \Illuminate\Support\Facades\DB::disableQueryLog();
        }

        // Compress output (gzip)
        if (function_exists('gzencode') &&
            strpos($request->header('Accept-Encoding'), 'gzip') !== false) {
            ob_start('ob_gzhandler');
        }

        $response = $next($request);

        // Add caching headers for static assets
        if ($request->getRequestUri() !== '/' &&
            (preg_match('/\.(js|css|png|jpg|jpeg|gif|ico|svg)$/i', $request->getRequestUri()))) {
            $response->header('Cache-Control', 'public, max-age=31536000'); // 1 year for static files
        } else if ($response->isSuccessful() && !$request->isJson()) {
            // HTML pages must not be publicly cached — they contain CSRF tokens and session data
            $response->header('Cache-Control', 'private, no-cache');
        }

        return $response;
    }
}
