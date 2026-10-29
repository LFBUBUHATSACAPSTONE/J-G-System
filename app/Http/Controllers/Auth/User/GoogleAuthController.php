<?php

namespace App\Http\Controllers\Auth\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\SignIn;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Throwable;

/**
 * Make sure OpenSSL can verify Google's certificates.
 *
 * XAMPP ships its own CA bundle (apache/bin/curl-ca-bundle.crt) but leaves
 * `openssl.cafile` unset, so PHP's OpenSSL falls back to its built-in store,
 * which on this build has no issuer for www.googleapis.com. Socialite's token
 * exchange then fails with "unable to get local issuer certificate" and the
 * callback throws the generic "Google sign-in could not be completed" error.
 * Pointing OpenSSL at the XAMPP bundle fixes it without touching .env or the
 * Google Cloud configuration.
 */
if (is_file($caBundle = dirname(__DIR__, 7) . '/apache/bin/curl-ca-bundle.crt')) {
    // Socialite's token exchange runs through cURL (Guzzle), so set BOTH the
    // OpenSSL and cURL CA locations — either one alone leaves the other empty.
    ini_set('openssl.cafile', $caBundle);
    ini_set('curl.cainfo', $caBundle);
}

class GoogleAuthController extends Controller
{
    private function getProvider(): GoogleProvider
    {
        $provider = Socialite::driver('google');
        $caBundle = dirname(__DIR__, 7) . '/apache/bin/curl-ca-bundle.crt';
        if (is_file($caBundle)) {
            $provider->setHttpClient(new \GuzzleHttp\Client(['verify' => $caBundle]));
        }
        return $provider;
    }

    public function redirect(): RedirectResponse
    {
        $state = Str::random(40);
        $provider = $this->getProvider();

        try {
            // Keep the short-lived OAuth state in its own first-party cookie. This lets
            // the callback validate Google's response even if the session cookie changes
            // during the cross-site round trip.
            return $provider
                ->stateless()
                ->with(['state' => $state])
                ->redirect()
                ->withCookie($this->stateCookie($state));
        } catch (Throwable $exception) {
            Log::warning('Google sign-in redirect failed.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return $this->failure('Google sign-in could not be completed. Please try again.');
        }
    }

    public function callback(SignIn $signIn): RedirectResponse
    {
        try {
            $stateCookie = (string) request()->cookie('google_oauth_state', '');
            $stateParameter = (string) request()->query('state', '');

            if ($stateCookie === '' || $stateParameter === '' || ! hash_equals($stateCookie, $stateParameter)) {
                return $this->failure('Your Google sign-in session expired...');
            }

            /** @var GoogleProvider $provider */
            $provider = $this->getProvider();
            $googleUser = $provider->stateless()->user();
            $googleId = (string) $googleUser->getId();
            $email = strtolower(trim((string) ($googleUser->getEmail() ?? '')));
            $userData = $googleUser->user ?? [];
            $isEmailVerified = (bool) ($userData['email_verified'] ?? $userData['verified_email'] ?? false);

            if ($googleId === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) || ! $isEmailVerified) {
                return $this->failure('Google did not provide a verified email address for this account.');
            }

            $closed = User::onlyTrashed()
                ->where(fn ($query) => $query->where('google_id', $googleId)->orWhereRaw('LOWER(email) = ?', [$email]))
                ->exists();
            if ($closed) {
                return $this->failure('This account has been deleted and can no longer be used to sign in.');
            }

            $user = User::where('google_id', $googleId)->first();

            if (! $user) {
                $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

                if ($user && $user->google_id && $user->google_id !== $googleId) {
                    return $this->failure('This email is linked to a different Google account.');
                }

                if ($user) {
                    $user->google_id = $googleId;
                    $user->email_verified_at ??= now();
                    $user->save();
                } else {
                    $displayName = trim((string) ($googleUser->getName() ?: strstr($email, '@', true) ?: $email));
                    $nameParts = preg_split('/\s+/', $displayName, 2) ?: [];

                    $user = User::create([
                        'first_name' => $nameParts[0] ?? $displayName,
                        'last_name' => $nameParts[1] ?? '',
                        'email' => $email,
                        'google_id' => $googleId,
                        'password' => Hash::make(Str::random(40)),
                    ]);
                    $user->email_verified_at = now();
                    $user->save();
                }
            }

            // Admin accounts belong on the admin side; the customer area is `role:user` only.
            if ($user->isAdmin()) {
                $signIn->admin($user);
            } else {
                $signIn->customer($user);
            }

            // Regenerate session to ensure security and prevent session fixation
            request()->session()->regenerate();

            if ($user->isAdmin()) {
                return redirect()->route('admin.dashboard')->withCookie($this->forgetStateCookie());
            }

            return redirect()->route('user.landing')->withCookie($this->forgetStateCookie());
        } catch (Throwable $exception) {
            Log::warning('Google sign-in failed.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return $this->failure('Google sign-in could not be completed. Please try again.');
        }
    }

    private function failure(string $message): RedirectResponse
    {
        return redirect()->route('user.landing')
            ->with('auth_error', $message)
            ->withCookie($this->forgetStateCookie());
    }

    private function stateCookie(string $state)
    {
        return cookie(
            'google_oauth_state',
            $state,
            5,
            config('session.path', '/'),
            config('session.domain'),
            config('session.secure'),
            true,
            false,
            config('session.same_site', 'lax'),
        );
    }

    private function forgetStateCookie()
    {
        return cookie()->forget(
            'google_oauth_state',
            config('session.path', '/'),
            config('session.domain'),
        );
    }
}
