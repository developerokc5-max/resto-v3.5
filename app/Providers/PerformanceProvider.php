<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

class PerformanceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Set memory limits based on environment
        if (config('performance.memory.http_memory_limit')) {
            ini_set('memory_limit', config('performance.memory.http_memory_limit') . 'M');
        }

        // Set execution time limit
        if (config('performance.timeout.web_default')) {
            set_time_limit(config('performance.timeout.web_default'));
        }

        // Setup database event listeners after app is fully booted
        // Only if database is available
        if ($this->app->bound('db')) {
            try {
                // Enable query logging for debugging (if enabled in config)
                if (config('performance.queries.logging_enabled')) {
                    DB::enableQueryLog();
                }

                // Listen to database queries for slow query detection
                if (config('performance.queries.logging_enabled')) {
                    DB::listen(function ($query) {
                        $slowThreshold = config('performance.queries.slow_query_threshold');

                        if ($query->time > $slowThreshold) {
                            \Illuminate\Support\Facades\Log::warning('Slow Query Detected', [
                                'time' => $query->time . 'ms',
                                'query' => $query->sql,
                                'bindings' => $query->bindings,
                            ]);
                        }
                    });
                }

                // Disable query log in production to save memory
                if (app()->environment('production')) {
                    DB::disableQueryLog();
                }
            } catch (\Exception $e) {
                // Database not available during bootstrap, skip
            }
        }
    }
}
