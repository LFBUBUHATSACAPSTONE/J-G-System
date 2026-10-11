<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerificationCodeMail;
use App\Models\User;
use App\Models\VerificationCode;
use App\Services\ModalAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class ModalVerificationController extends Controller
{
    public function verificationConfirm(Request $request, ModalAuthService $authService): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
            'context' => ['nullable', Rule::in(['signup', 'reset'])],
            'verification_token' => ['required', 'string'],
        ]);

        try {
            $payload = Crypt::decryptString((string) $validated['verification_token']);
            $payload = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
            $verification = VerificationCode::findOrFail($payload['id']);
        } catch (\Throwable) {
            return response()->json([
                'message' => 'Verification session expired. Please start over.',
            ], 422);
        }

        $code = preg_replace('/\D+/', '', (string) $validated['code']);
        $context = (string) ($validated['context'] ?? 'reset');

        $result = DB::transaction(function () use ($verification, $code, $context): array {
            $verification = VerificationCode::whereKey($verification->id)->lockForUpdate()->first();

            if (! $verification
                || $verification->context !== $context
                || $verification->used_at
                || $verification->expires_at->isPast()
                || ! Hash::check($code, $verification->code_hash)) {
                return ['error' => 'Invalid or expired verification code.'];
            }

            if ($context === 'signup') {
                if ($verification->pending_data !== null) {
                    try {
                        $registration = json_decode(
                            Crypt::decryptString($verification->pending_data),
                            true,
                            512,
                            JSON_THROW_ON_ERROR,
                        );
                    } catch (\Throwable) {
                        return ['error' => 'Registration data expired. Please start over.'];
                    }

                    $user = $verification->user_id
                        ? User::whereKey($verification->user_id)->lockForUpdate()->first()
                        : User::whereRaw('LOWER(email) = ?', [strtolower($registration['email'])])->first();

                    if ($user && $user->email_verified_at) {
                        return ['error' => 'This email is already registered. Please log in or use Forgot Password.'];
                    }

                    if (! $user) {
                        $user = new User;
                    }

                    $user->first_name = $registration['first_name'];
                    $user->last_name = $registration['last_name'];
                    $user->email = $registration['email'];
                    $user->phone = $registration['phone'];
                    $user->password = $registration['password'];
                    $user->email_verified_at = now();
                    $user->save();

                    $verification->user_id = $user->id;
                    $verification->pending_data = null;
                } elseif ($verification->user_id) {
                    User::whereKey($verification->user_id)->update([
                        'email_verified_at' => now(),
                    ]);
                }
            }

            $verification->used_at = now();
            $verification->save();

            return ['ok' => true, 'identifier' => $verification->identifier];
        });

        if (isset($result['error'])) {
            return response()->json([
                'message' => $result['error'],
            ], 422);
        }

        if ($context === 'signup') {
            return response()->json(['ok' => true]);
        }

        $email = $authService->resolveEmailFromIdentifier($result['identifier']);

        return response()->json([
            'token' => Crypt::encryptString((string) $result['identifier']),
            'email' => $email,
        ]);
    }

    public function verificationResend(Request $request, ModalAuthService $authService): JsonResponse
    {
        if (($request->input('context') === 'throttle') || $request->boolean('throttle') || $request->query('throttle')) {
            return response()->json([
                'message' => 'Too many requests. Please wait before requesting another code.',
            ], 429);
        }

        try {
            $payload = Crypt::decryptString((string) $request->input('verification_token'));
            $payload = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
            $oldVerification = VerificationCode::findOrFail($payload['id']);
        } catch (\Throwable) {
            return response()->json([
                'message' => 'Verification session expired. Please start over.',
            ], 422);
        }

        try {
            if ($oldVerification->context === 'signup' && $oldVerification->pending_data !== null) {
                $registration = json_decode(
                    Crypt::decryptString($oldVerification->pending_data),
                    true,
                    512,
                    JSON_THROW_ON_ERROR,
                );
                $code = (string) random_int(100000, 999999);
                $verification = VerificationCode::create([
                    'user_id' => $oldVerification->user_id,
                    'identifier' => $oldVerification->identifier,
                    'context' => 'signup',
                    'code_hash' => Hash::make($code),
                    'pending_data' => $oldVerification->pending_data,
                    'expires_at' => now()->addMinutes(10),
                ]);
                Mail::to($registration['email'])->send(new VerificationCodeMail($code, 'account registration'));
                $destination = $authService->maskIdentifier($registration['email']);
            } else {
                $resendUser = $oldVerification->user_id ? User::find($oldVerification->user_id) : null;
                $verification = $authService->issueVerification(
                    $resendUser,
                    $oldVerification->identifier,
                    $oldVerification->context === 'signup' ? 'account registration' : 'password reset',
                );
                $destination = $authService->maskIdentifier((string) $resendUser?->email);
            }
        } catch (\Throwable) {
            return response()->json([
                'message' => 'We could not resend the verification code. Please check the delivery configuration and try again.',
            ], 503);
        }

        $oldVerification->update(['used_at' => now()]);
        $verificationToken = $verification instanceof VerificationCode
            ? Crypt::encryptString(json_encode(['id' => $verification->id]))
            : $verification['token'];

        return response()->json([
            'destination' => $destination,
            'verification_token' => $verificationToken,
        ]);
    }
}