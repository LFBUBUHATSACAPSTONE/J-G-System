<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * POST /admin/logout (route name `admin.logout`).
 *
 * Terminates the admin's authenticated session for real: logs the `web` guard out, invalidates the
 * session and re-keys the CSRF token, so the browser cannot go back into the panel. A page request
 * is redirected to the sign-in card; a fetch() request gets 200 {ok, message, redirect}. Safe to
 * call twice: an already signed-out session still succeeds.
 */
class AdminLogoutController extends Controller
{
    public function logout(Request $request): JsonResponse|RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

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
