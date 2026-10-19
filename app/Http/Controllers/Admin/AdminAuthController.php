<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * Admin sign-in gate (routes/admin/login.php).
 *
 *   GET  /admin  the sign-in card (HTML). A signed-in admin is sent straight to the dashboard; a
 *                signed-in NON-admin sees the card with the "no admin access" notice.
 *   POST /admin/login  JSON only: signs the admin in.
 *
 * Contract (resources/js/admin-login.js, same shape the stub used to answer):
 *   200 {ok, redirect}                    signed in; the page goes to `redirect`
 *   422 {message, errors{email|password}} rejected
 *   403 {message}                         signed in, but the account's role is not 'admin'
 *   429 {message}                         too many attempts
 *
 * Who is an admin: users.role === 'admin'. Admin accounts are created by the owner (there is no
 * admin sign-up); every account created through sign-up is a plain 'user'.
 */
class AdminAuthController extends Controller
{
    /** Failed sign-ins allowed per email + IP before 429 (same numbers as Account > Security). */
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 60;

    private const CREDENTIALS_MESSAGE = "We couldn't sign you in — check that your email and password are correct.";

    private const NO_ACCESS_MESSAGE = "This account doesn't have admin access.";

    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        // Already signed in as an admin: there is nothing to sign in to.
        if ($user?->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login', [
            // Signed in but not an admin: the view prints the notice above the card.
            'noAccess' => $user !== null,
        ]);
    }

    public function login(Request $request): JsonResponse
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

        // Signed in FIRST: a non-admin keeps the session (the card then shows "no access"),
        // which is the 403 state the front end documents.
        Auth::login($user, (bool) $request->boolean('remember'));
        $request->session()->regenerate();

        if (! $user->isAdmin()) {
            return response()->json(['message' => self::NO_ACCESS_MESSAGE], 403);
        }

        return response()->json([
            'ok' => true,
            'redirect' => route('admin.dashboard'),
        ]);
    }

    /**
     * Case-insensitive email lookup, the same one the user-side modal uses
     * (App\Services\ModalAuthService::findUserByIdentifier), restricted to the email field:
     * the admin card only ever asks for an email.
     */
    private function findUser(string $email): ?User
    {
        return User::query()->whereRaw('LOWER(email) = ?', [strtolower($email)])->first();
    }
}
