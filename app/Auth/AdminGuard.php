<?php

namespace App\Auth;

use App\Models\User;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;

/**
 * The admin session guard.
 *
 * The customer area and the admin panel used to share ONE session (`web`), so signing in on one
 * side threw the other side out and both sides shared the same "remember me" cookie. This guard
 * gives the panel its own session key (see SessionGuard::getName()) and its own recaller cookie,
 * so both sides can be signed in at the same time without evicting each other.
 *
 * Two rules keep the panel safe:
 *
 *   1. Only an admin may be signed in here. A customer row that somehow reaches this guard is
 *      never returned as the signed-in user.
 *   2. When this guard has no session of its own, the customer guard is asked. An admin who is
 *      signed in there (the tests sign admins in with actingAs(), and the admin sign-in card also
 *      signs the account in on the customer guard when that one is free) still counts as signed
 *      in for the panel. A customer never does.
 */
class AdminGuard extends SessionGuard
{
    /**
     * The signed-in admin, or null. Never a customer.
     */
    public function user(): ?Authenticatable
    {
        $user = parent::user();

        if ($user) {
            return $this->isAdmin($user) ? $user : null;
        }

        // No admin session of our own: an admin signed in on the customer guard still counts.
        $fallback = Auth::guard('web')->user();

        return $fallback && $this->isAdmin($fallback) ? $fallback : null;
    }

    /**
     * The id of the signed-in admin. SessionGuard::id() falls back to the raw session value, which
     * would hand out a customer id here; this always answers with the signed-in account's id.
     */
    public function id()
    {
        return $this->user()?->getAuthIdentifier();
    }

    private function isAdmin(Authenticatable $user): bool
    {
        return $user instanceof User && $user->isAdmin();
    }
}
