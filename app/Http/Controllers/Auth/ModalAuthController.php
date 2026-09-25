<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerificationCodeMail;
use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;

class ModalAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $user = $this->findUserByIdentifier((string) $validated['identifier']);

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

    public function forgotPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
        ]);

        $identifier = trim((string) $validated['identifier']);

        if (! $this->findUserByIdentifier($identifier)) {
            return response()->json([
                'message' => "We couldn't find an account with that email or phone number.",
            ], 422);
        }

        $user = $this->findUserByIdentifier($identifier);
        $verification = $this->issueVerification($user, $identifier, 'password reset');

        return response()->json([
            'destination' => $this->maskIdentifier($identifier),
            'verification_token' => $verification['token'],
        ]);
    }

    public function verificationConfirm(Request $request): JsonResponse
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

        if ($verification->context !== $context
            || $verification->used_at
            || $verification->expires_at->isPast()
            || ! Hash::check($code, $verification->code_hash)) {
            return response()->json([
                'message' => 'Invalid or expired verification code.',
            ], 422);
        }

        $verification->update(['used_at' => now()]);

        if ($context === 'signup') {
            if ($verification->user_id) {
                User::whereKey($verification->user_id)->update([
                    'email_verified_at' => now(),
                ]);
            }

            return response()->json(['ok' => true]);
        }

        $email = $this->resolveEmailFromIdentifier($verification->identifier);

        return response()->json([
            'token' => Crypt::encryptString((string) $verification->identifier),
            'email' => $email,
        ]);
    }

    public function verificationResend(Request $request): JsonResponse
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

        $verification = $this->issueVerification(
            $oldVerification->user_id ? User::find($oldVerification->user_id) : null,
            $oldVerification->identifier,
            $oldVerification->context === 'signup' ? 'account registration' : 'password reset',
        );

        return response()->json([
            'destination' => $this->maskIdentifier($oldVerification->identifier),
            'verification_token' => $verification['token'],
        ]);
    }

    public function passwordUpdate(Request $request): JsonResponse
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

        $user = $this->findUserByIdentifier((string) $identifier);

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

    private function findUserByIdentifier(string $identifier): ?User
    {
        $value = trim($identifier);

        if ($value === '') {
            return null;
        }

        if (str_contains($value, '@')) {
            return User::whereRaw('LOWER(email) = ?', [strtolower($value)])->first();
        }

        $phone = preg_replace('/\D+/', '', $value);

        return User::where('phone', $phone)->first();
    }

    private function resolveEmailFromIdentifier(string $identifier): string
    {
        $value = trim($identifier);

        if (str_contains($value, '@')) {
            return strtolower($value);
        }

        $user = $this->findUserByIdentifier($value);

        return $user?->email ? strtolower((string) $user->email) : strtolower($value);
    }

    private function maskIdentifier(string $identifier): string
    {
        $value = trim($identifier);

        if (str_contains($value, '@')) {
            [$local, $domain] = explode('@', $value, 2);
            $visible = mb_substr($local, 0, 1);

            return $visible . '***@' . $domain;
        }

        $digits = preg_replace('/\D/', '', $value);
        $last = substr($digits, -3);

        return str_repeat('*', max(strlen($digits) - 3, 0)) . $last;
    }

    private function issueVerification(?User $user, string $identifier, string $purpose): array
    {
        if (! $user?->email) {
            abort(response()->json([
                'message' => 'Email verification is required for this flow.',
            ], 422));
        }

        $code = (string) random_int(100000, 999999);
        $verification = VerificationCode::create([
            'user_id' => $user->id,
            'identifier' => $identifier,
            'context' => $purpose === 'account registration' ? 'signup' : 'reset',
            'code_hash' => Hash::make($code),
            'expires_at' => Carbon::now()->addMinutes(10),
        ]);

        Mail::to($user->email)->send(new VerificationCodeMail($code, $purpose));

        return [
            'token' => Crypt::encryptString(json_encode(['id' => $verification->id])),
        ];
    }
}
