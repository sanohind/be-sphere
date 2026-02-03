<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // ✅ Trust Cloudflare proxies
        $middleware->trustProxies(
            at: '*',
            headers: \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_FOR |
                     \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_HOST |
                     \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_PORT |
                     \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_PROTO |
                     \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_AWS_ELB
        );

        // ✅ Use custom TrustProxies middleware for Cloudflare
        $middleware->use([
            \App\Http\Middleware\TrustProxies::class,
            \App\Http\Middleware\CloudflareSecurityHeaders::class, // ✅ Add security headers
            \Illuminate\Http\Middleware\HandleCors::class, // ✅ Handle CORS for API requests
        ]);

        // ✅ Register middleware aliases
        $middleware->alias([
            'jwt.verify' => \App\Http\Middleware\JWTMiddleware::class,
            'user.active' => \App\Http\Middleware\CheckUserActive::class,
            'role.level' => \App\Http\Middleware\CheckRoleLevel::class,
            
            // ✅ OIDC rate limiting (for future use)
            'throttle.oauth' => \Illuminate\Routing\Middleware\ThrottleRequests::class.':oauth',
            'throttle.discovery' => \Illuminate\Routing\Middleware\ThrottleRequests::class.':discovery',
        ]);

        // Session middleware is now available since sessions table has been created
        // This allows web routes to use session if needed (e.g., CSRF protection)

        // Optional: Apply middleware to all API routes
        // $middleware->api(prepend: [
        //     \App\Http\Middleware\JWTMiddleware::class,
        // ]);

        // ✅ Rate limiting for API routes
        $middleware->throttleApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
