<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

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
        // Define API rate limiter required by throttle middleware
        RateLimiter::for('api', function (Request $request) {
            $key = optional($request->user())->id ?: $request->ip();
            return [
                Limit::perMinute(60)->by($key),
            ];
        });

         // Optional: Register middleware groups programmatically
        // Route::middlewareGroup('sso', [
        //     \\App\\Http\\Middleware\\JWTMiddleware::class,
        //     \\App\\Http\\Middleware\\CheckUserActive::class,
        // ]);
    }
}
