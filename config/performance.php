<?php

/**
 * Performance Optimization Configuration
 *
 * This file contains all performance-related configurations for the application.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Query Optimization
    |--------------------------------------------------------------------------
    |
    | Configure database query optimization settings
    |
    */
    'queries' => [
        // Enable query caching for frequently used queries
        'cache_enabled' => true,

        // Default cache TTL for query results (in seconds)
        'cache_ttl' => 300, // 5 minutes

        // Log slow queries (queries taking longer than this in ms)
        'slow_query_threshold' => 100,

        // Enable query logging (disable in production)
        'logging_enabled' => env('APP_ENV') !== 'production',

        // Maximum number of queries allowed per request
        'max_queries_per_request' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Cache optimization settings
    |
    */
    'cache' => [
        // Cache store to use (file, redis, memcached)
        'default' => env('CACHE_STORE', 'file'),

        // Enable automatic cache warming
        'warming_enabled' => true,

        // Cache warming TTL (how often to refresh pre-cached data)
        'warming_interval' => 300, // 5 minutes

        // Paths to warm cache for (frequently accessed data)
        'warm_paths' => [
            'dashboard',
            'platform_status',
            'shop_data',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Memory Optimization
    |--------------------------------------------------------------------------
    |
    | Memory usage settings
    |
    */
    'memory' => [
        // Chunk size for large dataset operations (number of records per chunk)
        'chunk_size' => 1000,

        // Memory limit for CLI commands (in MB)
        'cli_memory_limit' => 256,

        // Memory limit for HTTP requests (in MB)
        'http_memory_limit' => 128,

        // Garbage collection interval (number of requests)
        'gc_interval' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Asset Optimization
    |--------------------------------------------------------------------------
    |
    | Asset loading and bundling settings
    |
    */
    'assets' => [
        // Enable asset minification
        'minify' => env('APP_ENV') === 'production',

        // Enable asset compression (gzip)
        'compress' => true,

        // Cache static assets for 1 year (in seconds)
        'cache_duration' => 31536000,

        // Enable lazy loading for images
        'lazy_load_images' => true,

        // Enable CSS optimization (remove unused styles with Tailwind)
        'css_optimization' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Connection Pooling
    |--------------------------------------------------------------------------
    |
    | Connection pool settings for better concurrency
    |
    */
    'connection_pool' => [
        // Minimum number of connections to keep open
        'min_connections' => 2,

        // Maximum number of connections allowed
        'max_connections' => 10,

        // Connection idle timeout (in seconds)
        'idle_timeout' => 900,

        // Connection max lifetime (in seconds)
        'max_lifetime' => 3600,
    ],

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | Request execution timeout settings
    |
    */
    'timeout' => [
        // Default timeout for web requests (in seconds)
        'web_default' => 300, // 5 minutes

        // Timeout for API requests (in seconds)
        'api_default' => 60, // 1 minute

        // Timeout for heavy operations like export/import (in seconds)
        'heavy_operations' => 600, // 10 minutes

        // Timeout for CLI commands (in seconds)
        'cli_default' => 300, // 5 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Query Batching
    |--------------------------------------------------------------------------
    |
    | Settings for batch query operations
    |
    */
    'batch_operations' => [
        // Enable query batching for bulk operations
        'enabled' => true,

        // Batch size for bulk inserts/updates
        'insert_batch_size' => 500,

        // Batch size for bulk deletes
        'delete_batch_size' => 100,

        // Enable transaction wrapping for data consistency
        'use_transactions' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring
    |--------------------------------------------------------------------------
    |
    | Monitor and log performance metrics
    |
    */
    'monitoring' => [
        // Enable performance monitoring
        'enabled' => true,

        // Log performance metrics to file
        'log_metrics' => env('APP_ENV') !== 'production',

        // Performance metrics log file
        'metrics_log' => storage_path('logs/performance.log'),

        // Enable detailed timing information
        'detailed_timing' => env('APP_DEBUG', false),
    ],
];
