<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\ResetDefaultGuard;
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
    ->withMiddleware(function (Middleware $middleware) {
        // `role:admin` / `role:user` — the role gate applied on top of `auth` (see EnsureRole).
        $middleware->alias([
            'role' => EnsureRole::class,
        ]);

        // Every request starts on the customer guard again: the `auth:` middleware switches the
        // default guard for the request it guards, and that switch must not leak into the next
        // request (see App\Http\Middleware\ResetDefaultGuard).
        $middleware->append(ResetDefaultGuard::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
