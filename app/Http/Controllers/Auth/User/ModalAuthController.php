<?php

namespace App\Http\Controllers\Auth\User;

use App\Http\Controllers\Controller;
use App\Services\Auth\ModalAuthService;
use App\Services\Auth\SignIn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ModalAuthController extends Controller
{
    public function login(Request $request, ModalAuthService $authService, SignIn $signIn): JsonResponse
    {
        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $user = $authService->findUserByIdentifier((string) $validated['identifier']);

        if (! $user || ! Hash::check((string) $validated['password'], (string) $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 422);
        }

        // The customer area is for the `user` role. Admin accounts sign in through /admin instead;
        // letting them in here would only get them bounced by the `role:user` gate.
        if ($user->isAdmin()) {
            return response()->json([
                'message' => 'This account has admin access. Please sign in through the admin portal.',
            ], 403);
        }

        // The customer session only: signing in here never touches the admin session, so a staff
        // member who is already signed in on the panel keeps that session.
        $signIn->customer($user, true);
        $request->session()->regenerate();

        return response()->json([
            'ok' => true,
            'redirect' => route('user.landing'),
        ]);
    }

}
