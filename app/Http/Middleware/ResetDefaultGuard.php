<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Puts the default guard back on the customer session before every request.
 *
 * The `auth:` middleware switches the default guard for the request it guards (the customer
 * guard for the customer area, the admin guard for the panel). That switch is kept in the
 * config, so it would leak into the NEXT request when one process handles more than one
 * request (the test suite does exactly that). Every request therefore starts on the same
 * guard again, and each area picks the one it needs.
 */
class ResetDefaultGuard
{
    public function __construct(private readonly Application $app)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        Auth::shouldUse($this->app->make('auth.start_guard'));

        return $next($request);
    }
}
