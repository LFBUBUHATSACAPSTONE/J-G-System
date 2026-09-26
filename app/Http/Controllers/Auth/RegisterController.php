<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Mail\VerificationCodeMail;
use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

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

    /**
     * Get a validator for an incoming registration request.
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @return User
     */
    protected function create(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $identifier = trim((string) ($validated['identifier'] ?? ''));

        if (! ($validated['email'] ?? null)) {
            return response()->json([
                'message' => 'A valid email address is required to receive the verification code.',
            ], 422);
        }

        try {
            [, $verification] = DB::transaction(function () use ($validated, $identifier): array {
                $user = User::where('email', $validated['email'])->first();

                if ($user) {
                    $user->update([
                        'name' => trim($validated['first_name'] . ' ' . $validated['last_name']),
                        'phone' => $validated['phone'] ?? null,
                        'password' => Hash::make($validated['password']),
                    ]);
                    VerificationCode::where('user_id', $user->id)
                        ->where('context', 'signup')
                        ->delete();
                } else {
                    $user = User::create([
                        'name'  => trim($validated['first_name'] . ' ' . $validated['last_name']),
                        'email' => $validated['email'],
                        'phone' => $validated['phone'] ?? null,
                        'password' => Hash::make($validated['password']),
                    ]);
                }

                $code = (string) random_int(100000, 999999);
                $verification = VerificationCode::create([
                    'user_id' => $user->id,
                    'identifier' => $identifier,
                    'context' => 'signup',
                    'code_hash' => Hash::make($code),
                    'expires_at' => now()->addMinutes(10),
                ]);

                Mail::to($user->email)->send(new VerificationCodeMail($code, 'account registration'));

                return [$user, $verification];
            });
        } catch (\Throwable) {
            return response()->json([
                'message' => 'We could not send the verification email. Please check the mail configuration and try again.',
            ], 503);
        }

        if (str_contains($identifier, '@')) {
            [$local, $domain] = explode('@', $identifier, 2);
            $visible = mb_substr($local, 0, 1);
            $destination = $visible . '***@' . $domain;
        } else {
            $digits = preg_replace('/\D/', '', $identifier);
            $last = substr($digits, -3);
            $destination = str_repeat('*', max(strlen($digits) - 3, 0)) . $last;
        }

        return response()->json([
            'destination' => $destination,
            'verification_token' => Crypt::encryptString(json_encode(['id' => $verification->id])),
        ], 201);
    }
}
