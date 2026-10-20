<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Role gate for both sides of the system. Applied after `auth`, so the user is always signed in
 * by the time it runs:
 *
 *   Route::middleware(['auth', 'role:admin'])  the admin panel
 *   Route::middleware(['auth', 'role:user'])   the customer account area
 *
 * A row with no role (legacy data, or a test that builds its own schema) counts as a regular
 * user, so the customer side keeps working; the admin side always needs an explicit 'admin'.
 *
 * JSON requests get a clean 401/403; page requests are redirected to where the account belongs
 * (an admin is sent to their dashboard, anyone else to the admin sign-in card, which already
 * prints the "no admin access" notice for a signed-in non-admin).
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Defensive: `auth` normally answers guests first. Kept so the middleware is safe on
        // its own, too.
        if (! $user) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthenticated.'], 401)
                : redirect()->route('user.landing');
        }

        $role = $user->role ?: User::ROLE_USER;

        if (! in_array($role, $roles, true)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $role === User::ROLE_ADMIN
                        ? 'Admin accounts cannot use the customer area.'
                        : "This account doesn't have access to this area.",
                ], 403);
            }

            return redirect()->route($role === User::ROLE_ADMIN ? 'admin.dashboard' : 'admin.login');
        }

        return $next($request);
    }
}
