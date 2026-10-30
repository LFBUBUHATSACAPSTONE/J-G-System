<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Role gate for both sides of the system. Applied after `auth`, so the account is always signed
 * in by the time it runs:
 *
 *   Route::middleware(['auth:web,admin', 'role:admin'])  the admin panel
 *   Route::middleware(['auth:web', 'role:user'])          the customer account area
 *
 * Each area reads the session that belongs to it: the panel reads the admin session, the
 * customer area reads the customer session. A row with no role (legacy data, or a test that
 * builds its own schema) counts as a regular user, so the customer side keeps working; the admin
 * side always needs an explicit 'admin'.
 *
 * JSON requests get a clean 401/403; page requests are redirected to where the account belongs
 * (an admin is sent to their dashboard, anyone else to the admin sign-in card, which already
 * prints the "no admin access" notice for a signed-in non-admin).
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $area = $this->area($roles);
        $user = $this->accountOf($area);

        if (! $user) {
            // Nobody is signed in for this area. If somebody IS signed in on the other side, the
            // request is refused instead of being treated as a guest.
            $other = $this->accountOf($area === 'admin' ? 'user' : 'admin');

            if ($other) {
                return $this->forbidden($request, $other);
            }

            // Defensive: `auth` normally answers guests first. Kept so the middleware is safe on
            // its own, too.
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthenticated.'], 401)
                : redirect()->route($area === 'admin' ? 'admin.login' : 'user.landing');
        }

        $role = $user->role ?: User::ROLE_USER;

        if (! in_array($role, $roles, true)) {
            return $this->forbidden($request, $user);
        }

        // Additional check to ensure admins can't access user routes and vice versa
        if ($area === 'admin' && $role === User::ROLE_USER) {
            return $this->forbidden($request, $user);
        }

        if ($area === 'user' && $role === User::ROLE_ADMIN) {
            return $this->forbidden($request, $user);
        }

        return $next($request);
    }

    /**
     * The area a set of roles belongs to: the panel or the customer area.
     */
    private function area(array $roles): string
    {
        return in_array(User::ROLE_ADMIN, $roles, true) ? 'admin' : 'user';
    }

    /**
     * The account signed in for that area. The panel only ever answers with an admin; the
     * customer area answers with whoever is on the customer session (an admin who signed in on
     * that side is bounced by the role check above).
     */
    private function accountOf(string $area): ?User
    {
        $user = Auth::guard($area === 'admin' ? 'admin' : 'web')->user();

        return $user instanceof User ? $user : null;
    }

    private function forbidden(Request $request, User $user): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $user->isAdmin()
                    ? 'Admin accounts cannot use the customer area.'
                    : "This account doesn't have access to this area.",
            ], 403);
        }

        return redirect()->route($user->isAdmin() ? 'admin.dashboard' : 'admin.login');
    }
}
