<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps signed-in regular users out of every admin URL while allowing guests to reach
 * the admin sign-in and password-recovery pages.
 */
class EnsureAdminArea
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = Auth::guard('admin')->user();
        if ($admin instanceof User) {
            return $next($request);
        }

        $customer = Auth::guard('web')->user();
        if ($customer instanceof User && ! $customer->isAdmin()) {
            return $request->expectsJson()
                ? response()->json(['message' => 'This account does not have access to the admin area.'], 403)
                : redirect()->route('user.landing');
        }

        return $next($request);
    }
}
