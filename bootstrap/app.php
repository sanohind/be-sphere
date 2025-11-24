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
        // Register middleware aliases
        $middleware->alias([
            'jwt.verify' => \App\Http\Middleware\JWTMiddleware::class,
            'user.active' => \App\Http\Middleware\CheckUserActive::class,
            'role.level' => \App\Http\Middleware\CheckRoleLevel::class,
        ]);

        // Session middleware is now available since sessions table has been created
        // This allows web routes to use session if needed (e.g., CSRF protection)

        // Optional: Apply middleware to all API routes
        // $middleware->api(prepend: [
        //     \App\Http\Middleware\JWTMiddleware::class,
        // ]);

        // Optional: Rate limiting for specific routes
        $middleware->throttleApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
