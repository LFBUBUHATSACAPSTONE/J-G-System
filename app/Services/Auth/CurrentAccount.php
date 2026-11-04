<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Contracts\Auth\Factory as AuthFactory;

/**
 * Who is signed in right now, and where that account belongs.
 *
 * The system has two separate sessions:
 *
 *   web    the customer session (the landing page, the booking flow, the account area)
 *   admin  the admin session (the panel under /admin)
 *
 * This service is the one place that answers "which account is this request working for?", so
 * the controllers, the routes and the middleware never have to guess. It also owns the
 * "where does this account go?" answers, so a landing page, a profile page and a sign-out
 * always point at the SAME place for the SAME account.
 */
class CurrentAccount
{
    public function __construct(private readonly AuthFactory $auth)
    {
    }

    /**
     * Whoever is signed in on the customer session. An admin can be there too: the admin sign-in
     * card also signs the account in on the customer session when that one is free.
     */
    public function customerSession(): ?User
    {
        $user = $this->auth->guard('web')->user();

        return $user instanceof User ? $user : null;
    }

    /**
     * The signed-in customer, if there is one. An admin row on the customer session is not a
     * customer: it belongs to the panel.
     */
    public function customer(): ?User
    {
        $user = $this->customerSession();

        return $user?->isUser() ? $user : null;
    }

    /**
     * The signed-in admin, if there is one. The admin guard never answers with a customer.
     */
    public function admin(): ?User
    {
        $user = $this->auth->guard('admin')->user();

        return $user instanceof User ? $user : null;
    }

    /**
     * The account this request is for: the admin when there is one, the customer otherwise.
     */
    public function account(): ?User
    {
        return $this->admin() ?? $this->customer();
    }

    /** True when the signed-in account is an admin. */
    public function isAdmin(): bool
    {
        return $this->admin() !== null;
    }

    /** True when the signed-in account is a customer. */
    public function isCustomer(): bool
    {
        return $this->customer() !== null;
    }

    /**
     * The home page of the signed-in account: the panel for an admin, the landing page for a
     * customer (and for a guest, who has to sign in there first).
     */
    public function landingRoute(): string
    {
        return $this->isAdmin() ? 'admin.dashboard' : 'user.landing';
    }

    /**
     * The profile page of the account on the customer session: the admin's own account page when
     * an admin is the one signed in there, the customer's profile tab otherwise.
     */
    public function profileRoute(): string
    {
        return $this->customerSession()?->isAdmin() ? 'admin.account' : 'user.account.profile';
    }

    /**
     * The sign-in page that belongs to the account that was signed in on the customer session:
     * the admin sign-in card for an admin, the landing page (which hosts the customer sign-in
     * modal) for a customer.
     */
    public function customerLoginRoute(): string
    {
        return $this->customerSession()?->isAdmin() ? 'admin.login' : 'user.landing';
    }

    /** The sign-in page of the panel. */
    public function adminLoginRoute(): string
    {
        return 'admin.login';
    }
}
