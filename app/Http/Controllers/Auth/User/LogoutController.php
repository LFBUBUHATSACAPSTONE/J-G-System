<?php

namespace App\Http\Controllers\Auth\User;

use App\Http\Controllers\Controller;
use App\Services\Auth\CurrentAccount;
use App\Services\Auth\SignOut;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


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
