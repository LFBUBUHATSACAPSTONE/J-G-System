<?php

namespace App\Http\Controllers\Auth\User;

use App\Http\Controllers\Controller;
use App\Services\Auth\CurrentAccount;
use App\Services\Auth\SignOut;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POST logout (route name `logout`), JSON only. Replaces the default Laravel UI logout, which answers
 * with a redirect. Safe to call twice: an already logged-out session still gets 200.
 *
 * Only the customer session ends here: an admin who is signed in on the panel keeps that session.
 * The browser is sent to the sign-in page that belongs to the account that was signed out (the
 * landing page for a customer, the admin sign-in card for an admin).
 *
 * Contract (resources/js/account.js): 200 {ok, message, redirect}
 */
class LogoutController extends Controller
{
    public function logout(Request $request, SignOut $signOut, CurrentAccount $account): JsonResponse
    {
        // Read the account BEFORE the sign-out: afterwards nobody is signed in any more.
        $redirect = route($account->customerLoginRoute());

        $signOut->customer($request);

        return response()->json([
            'ok' => true,
            'message' => 'You have been logged out.',
            'redirect' => $redirect,
        ]);
    }
}
