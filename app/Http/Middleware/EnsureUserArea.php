<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps signed-in admins in the admin area while allowing public visitors and users
 * to use the customer-facing routes.
 */
class EnsureUserArea
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = Auth::guard('admin')->user();

        if ($admin instanceof User) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Admin accounts cannot use the customer area.'], 403)
                : redirect()->route('admin.dashboard');
        }

        return $next($request);
    }
}
