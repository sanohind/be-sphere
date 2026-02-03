<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
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
        // ✅ Force HTTPS in production (for Cloudflare)
        if (config('app.env') === 'production' || config('app.force_https')) {
            URL::forceScheme('https');
        }

        // ✅ Define API rate limiter
        RateLimiter::for('api', function (Request $request) {
            $key = optional($request->user())->id ?: $request->ip();
            return [
                Limit::perMinute(60)->by($key),
            ];
        });

        // ✅ OAuth endpoints rate limiter (stricter for security)
        RateLimiter::for('oauth', function (Request $request) {
            $key = $request->ip();
            return [
                Limit::perMinute(100)->by($key)->response(function () {
                    return response()->json([
                        'error' => 'too_many_requests',
                        'error_description' => 'Rate limit exceeded. Please try again later.'
                    ], 429);
                }),
            ];
        });

        // ✅ Discovery endpoint rate limiter (more lenient)
        RateLimiter::for('discovery', function (Request $request) {
            $key = $request->ip();
            return [
                Limit::perMinute(200)->by($key),
            ];
        });
    }
}
