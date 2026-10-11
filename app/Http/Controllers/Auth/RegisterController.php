<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerificationCodeMail;
use App\Models\User;
use App\Models\VerificationCode;
use App\Services\ModalAuthService;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Throwable;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function register(Request $request, ModalAuthService $authService): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'identifier' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', PasswordRule::min(8)->mixedCase()->numbers()],
            'terms' => ['accepted'],
        ]);

        $identifier = trim((string) $validated['identifier']);
        $email = str_contains($identifier, '@') ? strtolower($identifier) : null;

        // Sign-up verifies ownership through email, so an email address is required.
        if ($email === null || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'message' => 'Please sign up with an email address so we can send your verification code.',
                'errors' => [
                    'identifier' => ['Please sign up with an email address so we can send your verification code.'],
                ],
            ], 422);
        }

        $phone = str_contains($identifier, '@')
            ? null
            : preg_replace('/\D+/', '', $identifier);

        // Block re-registration of an email that already has a verified account.
        $existing = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if ($existing && $existing->email_verified_at) {
            return response()->json([
                'message' => 'An account with this email is already verified. Please log in or use Forgot Password.',
                'errors' => [
                    'email' => ['An account with this email is already verified. Please log in or use Forgot Password.'],
                ],
            ], 422);
        }

        // Hold the registration details until the emailed code is confirmed. The account row is
        // only created in ModalVerificationController::verificationConfirm().
        $registration = [
            'first_name' => trim((string) $validated['first_name']),
            'last_name' => trim((string) $validated['last_name']),
            'email' => $email,
            'phone' => $phone,
            'password' => Hash::make((string) $validated['password']),
        ];

        try {
            $code = (string) random_int(100000, 999999);
            $verification = VerificationCode::create([
                'user_id' => $existing?->id,
                'identifier' => $identifier,
                'context' => 'signup',
                'code_hash' => Hash::make($code),
                'pending_data' => Crypt::encryptString((string) json_encode($registration)),
                'expires_at' => now()->addMinutes(10),
            ]);

            Mail::to($email)->send(new VerificationCodeMail($code, 'account registration'));
        } catch (Throwable $exception) {
            Log::error('Sign-up verification code could not be sent.', [
                'identifier' => $identifier,
                'exception' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'We could not send the verification code. Please try again later.',
            ], 503);
        }

        return response()->json([
            'destination' => $authService->maskIdentifier($email),
            'verification_token' => Crypt::encryptString((string) json_encode(['id' => $verification->id])),
        ], 201);
    }
}
