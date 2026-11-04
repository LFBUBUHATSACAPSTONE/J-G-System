<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Contracts\Auth\Factory as AuthFactory;

/**
 * Signs an account in on the session it belongs to.
 *
 * The two sides keep their own session, so signing in never throws the other side out:
 *
 *   customer  signs in on the `web` guard only.
 *   admin     signs in on the `admin` guard. The customer guard is only used when it is FREE,
 *             so an admin who signs in while a customer is signed in does not evict that
 *             customer. The customer guard keeps the admin too, because the panel and the old
 *             `Auth::user()` calls expect to find the signed-in account there.
 */
class SignIn
{
    public function __construct(private readonly AuthFactory $auth)
    {
    }

    /** Signs a customer in on the customer session. */
    public function customer(User $user, bool $remember = false): void
    {
        $this->auth->guard('web')->login($user, $remember);
    }

    /** Signs an admin in on the admin session (and on the customer session when it is free). */
    public function admin(User $user, bool $remember = false): void
    {
        $this->auth->guard('admin')->login($user, $remember);

        if ($this->customerSessionIsFree()) {
            $this->auth->guard('web')->login($user, $remember);
        }
    }

    /**
     * True when nobody is signed in on the customer session.
     */
    private function customerSessionIsFree(): bool
    {
        return $this->auth->guard('web')->user() === null;
    }
}
