<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Account > Security.
 *
 *   GET   user.account.security         the page (HTML), or the sign-in methods as JSON
 *   PATCH user.account.password.update  JSON only
 *
 * Contract (resources/js/account/security.js):
 *   200 {ok, message}
 *   422 {message, errors{current_password|password|password_confirmation: [msg]}}
 *   429 {message}  too many wrong current passwords
 *   401 {message}  (the `auth` middleware)
 */
class AccountSecurityController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 60;

    public function show(Request $request): View|JsonResponse
    {
        $user = $request->user();

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'sign_in_methods' => [
                    'email_password' => true,
                    'google' => filled($user->google_id),
                ],
            ]);
        }

        // `users.password` is NOT NULL and a Google sign-up gets a random one, so the database cannot
        // tell a Google-only account apart: the form is always offered (default of the view).
        return view('user.account.security', ['user' => $user, 'hasPassword' => true]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $limiterKey = 'account-password:'.$user->id;

        if (RateLimiter::tooManyAttempts($limiterKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($limiterKey);

            return response()->json([
                'message' => "Too many attempts. Please try again in {$seconds} seconds.",
            ], 429);
        }

        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'string'],
            // Same rule as the sign-up form's checklist (js/auth/validation.js getPasswordError).
            'password' => ['required', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ], [
            'current_password.required' => 'Enter your current password.',
            'password.required' => 'Enter a new password.',
            'password.confirmed' => 'The passwords do not match.',
        ]);

        if ($validator->fails()) {
            return $this->invalid($validator->errors()->toArray());
        }

        $data = $validator->validated();

        if (! Hash::check($data['current_password'], (string) $user->password)) {
            RateLimiter::hit($limiterKey, self::DECAY_SECONDS);

            return $this->invalid(['current_password' => ['Your current password is incorrect.']]);
        }

        if (Hash::check($data['password'], (string) $user->password)) {
            return $this->invalid(['password' => ['Your new password must be different from your current one.']]);
        }

        // The model's `hashed` cast hashes it. A new remember token ends "remember me" on other devices.
        $user->password = $data['password'];
        $user->setRememberToken(Str::random(60));
        $user->save();

        RateLimiter::clear($limiterKey);

        return response()->json([
            'ok' => true,
            'message' => 'Your password has been updated.',
        ]);
    }

    private function invalid(array $errors): JsonResponse
    {
        return response()->json([
            'message' => 'Please review the highlighted fields.',
            'errors' => $errors,
        ], 422);
    }
}
