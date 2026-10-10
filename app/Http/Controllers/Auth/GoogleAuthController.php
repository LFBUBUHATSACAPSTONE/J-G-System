<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        $callbackUrl = (string) config('services.google.redirect');
        $callbackHost = parse_url($callbackUrl, PHP_URL_HOST);

        if ($callbackHost && strcasecmp(request()->getHost(), $callbackHost) !== 0) {
            $redirectUrl = preg_replace('~/callback(?:\?.*)?$~', '/redirect', $callbackUrl);

            return redirect()->away($redirectUrl);
        }

        $state = Str::random(40);
        /** @var GoogleProvider $provider */
        $provider = Socialite::driver('google');
        $response = $provider
            ->stateless()
            ->with(['state' => $state])
            ->redirect();

        return $response->withCookie(cookie(
            'google_oauth_state',
            $state,
            10,
            '/',
            null,
            request()->isSecure() ,
            true,
            false,
            'lax',
        ));
    }

    public function callback(): RedirectResponse
    {
        try {
            $stateCookie = (string) request()->cookie('google_oauth_state', '');
            $stateParameter = (string) request()->query('state', '');

            if ($stateCookie === '' || $stateParameter === '' || ! hash_equals($stateCookie, $stateParameter)) {
                return $this->failure('Your Google sign-in session expired. Return to this site and start sign-in again in the same browser.');
            }

            /** @var GoogleProvider $provider */
            $provider = Socialite::driver('google');
            $googleUser = $provider->stateless()->user();
            $googleId = (string) $googleUser->getId();
            $email = strtolower(trim((string) $googleUser->getEmail()));
            $isEmailVerified = (bool) ($googleUser->user['verified_email'] ?? false);

            if ($googleId === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) || ! $isEmailVerified) {
                return $this->failure('Google did not provide a verified email address for this account.');
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
                    $user = User::create([
                        'name' => $googleUser->getName() ?: $email,
                        'email' => $email,
                        'google_id' => $googleId,
                        'password' => Hash::make(Str::random(40)),
                    ]);
                    $user->email_verified_at = now();
                    $user->save();
                }
            }

            Auth::login($user, true);
            request()->session()->regenerate();

            return redirect()->route('user.landing')->withCookie(cookie()->forget('google_oauth_state'));
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
            ->withCookie(cookie()->forget('google_oauth_state'));
    }
}