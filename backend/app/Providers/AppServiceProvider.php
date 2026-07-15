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
        RateLimiter::for('coffee-initiate', fn (Request $request) => Limit::perMinutes(10, 5)->by('coffee-ip:'.$request->ip()));
        RateLimiter::for('coffee-status', fn (Request $request) => Limit::perMinute(30)->by('coffee-status:'.$request->ip().':'.$request->route('payment')));
        RateLimiter::for('daraja-callback', fn (Request $request) => Limit::perMinute(300)->by('daraja-callback:'.$request->ip()));
    }
}
