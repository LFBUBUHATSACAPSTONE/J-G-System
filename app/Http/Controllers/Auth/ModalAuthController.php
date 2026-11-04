<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ModalAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ModalAuthController extends Controller
{
    public function login(Request $request, ModalAuthService $authService): JsonResponse
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

        Auth::login($user, (bool) ($validated['remember'] ?? false));

        return response()->json([
            'ok' => true,
            'redirect' => route('home'),
        ]);
    }

}
