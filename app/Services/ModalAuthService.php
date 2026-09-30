<?php

namespace App\Services;

use App\Mail\VerificationCodeMail;
use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class ModalAuthService
{
    public function findUserByIdentifier(string $identifier): ?User
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

    public function resolveEmailFromIdentifier(string $identifier): string
    {
        $value = trim($identifier);

        if (str_contains($value, '@')) {
            return strtolower($value);
        }

        $user = $this->findUserByIdentifier($value);

        return $user?->email ? strtolower((string) $user->email) : strtolower($value);
    }

    public function maskIdentifier(string $identifier): string
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

    public function issueVerification(?User $user, string $identifier, string $purpose): array
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