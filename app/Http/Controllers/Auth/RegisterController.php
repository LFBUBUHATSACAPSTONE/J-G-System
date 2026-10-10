<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Mail\VerificationCodeMail;
use App\Models\User;
use App\Models\VerificationCode;
use App\Services\ModalAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RegisterController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function register(RegisterRequest $request, ModalAuthService $authService): JsonResponse
    {
        $validated = $request->validated();
        $identifier = trim((string) ($validated['identifier'] ?? $validated['email'] ?? ''));
        $email = strtolower(trim((string) ($validated['email'] ?? '')));

        if ($email === '') {
            return response()->json([
                'message' => 'A valid email address is required to receive the verification code.',
            ], 422);
        }

        try {
            $verification = DB::transaction(function () use ($validated, $identifier, $email): VerificationCode {
                $existingUser = User::whereRaw('LOWER(email) = ?', [$email])->first();

                VerificationCode::where('identifier', $email)
                    ->where('context', 'signup')
                    ->whereNull('used_at')
                    ->update(['used_at' => now()]);

                $code = (string) random_int(100000, 999999);
                $verification = VerificationCode::create([
                    'user_id' => $existingUser?->id,
                    'identifier' => $email,
                    'context' => 'signup',
                    'code_hash' => Hash::make($code),
                    'pending_data' => Crypt::encryptString(json_encode([
                        'first_name' => trim($validated['first_name']),
                        'last_name' => trim($validated['last_name']),
                        'email' => $email,
                        'phone' => $validated['phone'] ?? null,
                        'password' => Hash::make($validated['password']),
                    ], JSON_THROW_ON_ERROR)),
                    'expires_at' => now()->addMinutes(10),
                ]);

                Mail::to($email)->send(new VerificationCodeMail($code, 'account registration'));

                return $verification;
            });
        } catch (\Throwable $exception) {
            Log::error('Registration verification email failed.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'We could not send the verification email. Please check the mail configuration and try again.',
            ], 503);
        }

        return response()->json([
            'destination' => $authService->maskIdentifier($email),
            'verification_token' => Crypt::encryptString(json_encode(['id' => $verification->id])),
        ], 201);
    }
}
