<?php

namespace App\Http\Controllers\Auth\Admin;

use App\Http\Controllers\Controller;
use App\Services\Auth\SignOut;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
