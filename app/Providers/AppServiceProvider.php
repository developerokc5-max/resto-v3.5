<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Force HTTPS on Render (reverse proxy terminates SSL, app runs on HTTP internally)
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }
    }
}
