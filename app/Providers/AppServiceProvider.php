<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('asistente', fn (Request $request) => [
            Limit::perMinute(6)->by('ia:minuto:'.$request->user()->id),
            Limit::perDay(60)->by('ia:dia:'.$request->user()->id),
        ]);
    }
}
