<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Di belakang reverse proxy (nginx / Render), percayai header X-Forwarded-*
        // agar scheme HTTPS dan host terbaca dengan benar. Atur TRUSTED_PROXIES
        // bila ingin membatasi ke IP proxy tertentu (dipisah koma).
        $trustedProxies = env('TRUSTED_PROXIES', '*');
        $middleware->trustProxies(at: $trustedProxies === '*'
            ? '*'
            : array_map('trim', explode(',', $trustedProxies)));

        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
            'owns.stock' => \App\Http\Middleware\EnsureStockOwnership::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
