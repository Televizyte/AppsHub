<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Middleware aliases (Laravel 12 style)
        $middleware->alias([
            'app.token' => \App\Http\Middleware\EnsureAppToken::class,

            // ✅ App-scoped API auth guards
            'app.auth_enabled' => \App\Http\Middleware\AppAuthEnabled::class,
            'app.sanctum_scope' => \App\Http\Middleware\AppSanctumScope::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
