<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ModalAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;

class ModalPasswordController extends Controller
{
    public function forgotPassword(Request $request, ModalAuthService $authService): JsonResponse
    {
        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
        ]);

        $identifier = trim((string) $validated['identifier']);
        $user = $authService->findUserByIdentifier($identifier);

        if (! $user) {
            return response()->json([
                'message' => "We couldn't find an account with that email or phone number.",
            ], 422);
        }

        try {
            $verification = $authService->issueVerification($user, $identifier, 'password reset');
        } catch (\Throwable) {
            return response()->json([
                'message' => 'We could not send the verification code. Please try again later.',
            ], 503);
        }

        return response()->json([
            'destination' => $authService->maskIdentifier((string) $user->email),
            'verification_token' => $verification['token'],
        ]);
    }

    public function passwordUpdate(Request $request, ModalAuthService $authService): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()],
        ]);

        try {
            $identifier = Crypt::decryptString((string) $validated['token']);
        } catch (\Throwable) {
            return response()->json([
                'message' => 'This reset link is invalid or has expired.',
            ], 422);
        }

        $user = $authService->findUserByIdentifier((string) $identifier);

        if (! $user || strtolower((string) $user->email) !== strtolower((string) $validated['email'])) {
            return response()->json([
                'message' => 'Could not update your password.',
                'errors' => [
                    'email' => ['We could not validate that account.'],
                ],
            ], 422);
        }

        $user->password = Hash::make((string) $validated['password']);
        $user->save();

        return response()->json(['ok' => true]);
    }
}