<?php

namespace App\Http\Controllers\Auth\Admin;

use App\Http\Controllers\Controller;
use App\Services\Auth\SignOut;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * POST /admin/logout (route name `admin.logout`).
 *
 * Terminates the admin's session for real: the admin session is logged out and the CSRF token is
 * re-keyed, so the browser cannot go back into the panel. A customer who is signed in on the
 * customer session keeps that session: the two sides never sign each other out. A page request
 * is redirected to the sign-in card; a fetch() request gets 200 {ok, message, redirect}. Safe to
 * call twice: an already signed-out session still succeeds.
 */
class AdminLogoutController extends Controller
{
    public function logout(Request $request, SignOut $signOut): JsonResponse|RedirectResponse
    {
        $signOut->admin($request);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'You have been logged out.',
                'redirect' => route('admin.login'),
            ]);
        }

        return redirect()->route('admin.login');
    }
}
