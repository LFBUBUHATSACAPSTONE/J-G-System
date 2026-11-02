<?php

namespace App\Http\Controllers\Auth\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\CurrentAccount;
use App\Services\Auth\SignIn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    /** Failed sign-ins allowed per email + IP before 429 (same numbers as Account > Security). */
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 60;

    private const CREDENTIALS_MESSAGE = "We couldn't sign you in — check that your email and password are correct.";

    private const NO_ACCESS_MESSAGE = "This account doesn't have admin access.";

    public function show(Request $request, CurrentAccount $account): View|RedirectResponse
    {
        // The panel has its own session: an admin signed in there has nothing to sign in to.
        if ($account->admin()) {
            return redirect()->route('admin.dashboard');
        }

        if ($account->customer()) {
            return redirect()->route('user.landing');
        }

        return view('admin.login', [
            'noAccess' => false,
        ]);
    }

    public function login(Request $request, SignIn $signIn): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ], [
            'email.required' => 'Enter your email address.',
            'email.email' => "That doesn't look like a valid email address.",
            'password.required' => 'Enter a password.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Please review the highlighted fields.',
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        $email = trim((string) $validator->validated()['email']);
        $limiterKey = 'admin-login:'.sha1(strtolower($email).'|'.(string) $request->ip());

        if (RateLimiter::tooManyAttempts($limiterKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($limiterKey);

            return response()->json([
                'message' => "Too many attempts. Please try again in {$seconds} seconds.",
            ], 429);
        }

        $user = $this->findUser($email);

        if (! $user || ! Hash::check((string) $validator->validated()['password'], (string) $user->password)) {
            RateLimiter::hit($limiterKey, self::DECAY_SECONDS);

            // One message under BOTH fields on purpose: the answer must never say which of the
            // two was wrong (js/admin-login.js shows these field messages).
            return response()->json([
                'message' => self::CREDENTIALS_MESSAGE,
                'errors' => [
                    'email' => [self::CREDENTIALS_MESSAGE],
                    'password' => [self::CREDENTIALS_MESSAGE],
                ],
            ], 422);
        }

        RateLimiter::clear($limiterKey);

        if (! $user->isAdmin()) {
            return response()->json(['message' => self::NO_ACCESS_MESSAGE], 403);
        }

        // The admin session is the panel's own: signing in here never throws a signed-in
        // customer out.
        $signIn->admin($user, (bool) $request->boolean('remember'));
        $request->session()->regenerate();

        return response()->json([
            'ok' => true,
            'redirect' => route('admin.dashboard'),
        ]);
    }

    /**
     * Case-insensitive email lookup, the same one the user-side modal uses
     * (App\Services\Auth\ModalAuthService::findUserByIdentifier), restricted to the email field:
     * the admin card only ever asks for an email.
     */
    private function findUser(string $email): ?User
    {
        return User::query()->whereRaw('LOWER(email) = ?', [strtolower($email)])->first();
    }
}
