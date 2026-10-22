<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Http\Request;

/**
 * Ends a session.
 *
 * The two sides keep their own session, so signing out only touches the side that asked for it.
 * The ONE exception is the same account: when an admin is signed in on both sides, signing out
 * of one of them signs that account out of the other one too, because it is the same person
 * asking to be signed out.
 *
 * The session is NOT invalidated here: both sessions live in the same session store, so
 * invalidating it would throw the other side out as well. The CSRF token is regenerated
 * instead, which is what the old invalidate() call was there for.
 */
class SignOut
{
    public function __construct(private readonly AuthFactory $auth)
    {
    }

    /** Ends the customer session (and the admin session of the same account, if there is one). */
    public function customer(Request $request): void
    {
        $user = $this->auth->guard('web')->user();

        $this->auth->guard('web')->logout();

        if ($user instanceof User && $this->isSameAccount($this->auth->guard('admin')->user(), $user)) {
            $this->auth->guard('admin')->logout();
        }

        $request->session()->regenerateToken();
    }

    /** Ends the admin session (and the customer session of the same account, if there is one). */
    public function admin(Request $request): void
    {
        $user = $this->auth->guard('admin')->user();

        $this->auth->guard('admin')->logout();

        if ($user instanceof User && $this->isSameAccount($this->auth->guard('web')->user(), $user)) {
            $this->auth->guard('web')->logout();
        }

        $request->session()->regenerateToken();
    }

    private function isSameAccount(mixed $other, User $user): bool
    {
        return $other instanceof User
            && $other->getAuthIdentifier() === $user->getAuthIdentifier();
    }
}
