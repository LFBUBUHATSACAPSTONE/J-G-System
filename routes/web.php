<?php

use Illuminate\Support\Facades\Route;

// Front-End Verification Testing
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/user/landing', function () {
    return view('user.landing');
})->name('user.landing');

Route::get('/user/booking', function () {
    return view('user.booking');
})->name('user.booking');

// Remove the argument once back-end is connected (this is use for the active stubs)
Auth::routes(['reset' => false]);

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// Route::post('/login', fn() => back())->name('login');
// Route::post('/register', fn() => back())->name('register');
// Route::post('/password/email', fn() => back())->name('password.email');
// Route::post('/verification/confirm', fn() => back())->name('verification.confirm');
// Route::post('/password/update', fn() => back())->name('password.update');

// Temporary front-end test stubs. Merge into routes/web.php, add
// `use Illuminate\Http\Request;` at the top, and keep
// Auth::routes(['reset' => false]) (laravel/ui) while these are in use.
// Delete each stub as its real controller replaces it.

// 
// Shared helper: mask an email or phone the way the real back end will
// eventually do it, so the UI can be tested against realistic destinations
// instead of one hardcoded string.
// 

if (!function_exists('jgAuthMaskIdentifier')) {
    function jgAuthMaskIdentifier(string $identifier): string
    {
        if (str_contains($identifier, '@')) {
            [$local, $domain] = explode('@', $identifier, 2);
            $visible = mb_substr($local, 0, 1);
            return $visible . '***@' . $domain;
        }

        $digits = preg_replace('/\D/', '', $identifier);
        $last = substr($digits, -3);
        return str_repeat('*', max(strlen($digits) - 3, 0)) . $last;
    }
}

// Test account: test@example.com / password123
Route::post(
    '/login',
    fn(Request $r) =>
    $r->input('identifier') === 'test@example.com' && $r->input('password') === 'password123'
        ? response()->json(['ok' => true])
        : response()->json(['message' => 'Invalid credentials.'], 422)
)->name('login');

// taken@example.com -> 422; anything else -> 200 with a masked destination
// built from whatever the user actually typed.
Route::post('/register', function (Request $r) {
    $identifier = (string) $r->input('identifier');

    if ($identifier === 'taken@example.com') {
        return response()->json([
            'message' => 'That email is already registered.',
            'errors' => ['identifier' => ['That email is already registered.']],
        ], 422);
    }

    return response()->json([
        'destination' => jgAuthMaskIdentifier($identifier),
        'verification_token' => Crypt::encryptString($identifier),
    ]);
})->name('register');

// Masked destination built from whatever the user typed, so Forgot Password
// -> Verification shows a realistic value instead of a hardcoded one.
// unknown@example.com -> 422, to exercise the "account not found" path.
Route::post('/password/email', function (Request $r) {
    $identifier = (string) $r->input('identifier');

    if ($identifier === 'unknown@example.com') {
        return response()->json(['message' => "We couldn't find an account with that email or phone number."], 422);
    }

    return response()->json([
        'destination' => jgAuthMaskIdentifier($identifier),
        'verification_token' => Crypt::encryptString($identifier),
    ]);
})->name('password.email');

// Test code: 123456. `context` ("signup" | "reset") comes from the request
// body so both flows can be tested independently — signup just needs a 200,
// reset needs a token + email for the New Password step.
Route::post('/verification/confirm', function (Request $r) {
    $code = implode('', (array) $r->input('code'));

    if ($code !== '123456') {
        return response()->json(['message' => 'Invalid verification code.'], 422);
    }

    if ($r->input('context') === 'signup') {
        return response()->json(['ok' => true]);
    }

    // reset context (default)
    return response()->json(['token' => 'test-token', 'email' => 'test@example.com']);
})->name('verification.confirm');

// Decrypts the identifier from `verification_token` (issued by register / password.email) and returns a fresh masked destination plus the token. Append ?throttle=1 to the resend URL in DevTools, or POST with{"context":"throttle"}, to exercise the 429 path. A missing or tampered token returns 422.
Route::post('/verification/resend', function (Request $r) {
    if ($r->input('context') === 'throttle' || $r->query('throttle')) {
        return response()->json(['message' => 'Too many requests. Please wait before requesting another code.'], 429);
    }

    try {
        $identifier = Crypt::decryptString((string) $r->input('verification_token'));
    } catch (\Throwable) {
        return response()->json(['message' => 'Verification session expired. Please start over.'], 422);
    }

    return response()->json([
        'destination' => jgAuthMaskIdentifier($identifier),
        'verification_token' => $r->input('verification_token'),
    ]);
})->name('verification.resend');

// weak/short passwords -> 422, so New Password's server-error path (as
// opposed to the client-side live validation) can also be exercised.
Route::post('/password/update', function (Request $r) {
    $password = (string) $r->input('password');

    if (strlen($password) < 8) {
        return response()->json([
            'message' => 'Could not update your password.',
            'errors' => ['password' => ['Password must be at least 8 characters.']],
        ], 422);
    }

    return response()->json(['ok' => true]);
})->name('password.update');



/* -----------------BOOKING---------------------- */
// Temporary front-end test stub for the Personal Information step. Merge
// into routes/web.php alongside auth-stub-routes.php. Delete once a real
// controller replaces it.

// taken@example.com -> 422 (exercises the field-error path); anything
// else -> 200.
Route::post('/booking/personal-information', function (Request $r) {
    if ($r->input('email') === 'taken@example.com') {
        return response()->json([
            'message' => 'That email is already associated with a booking.',
            'errors' => ['email' => ['That email is already associated with a booking.']],
        ], 422);
    }

    return response()->json(['ok' => true]);
})->name('booking.personal-information');

Route::view('/pricing', 'pricing');