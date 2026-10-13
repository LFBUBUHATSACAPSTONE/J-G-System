<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * POST logout (route name `logout`), JSON only. Replaces the default Laravel UI logout, which answers
 * with a redirect. Safe to call twice: an already logged-out session still gets 200.
 *
 * Contract (resources/js/account.js): 200 {ok, message, redirect}
 */
class LogoutController extends Controller
{
    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'ok' => true,
            'message' => 'You have been logged out.',
            'redirect' => route('user.landing'),
        ]);
    }
}
