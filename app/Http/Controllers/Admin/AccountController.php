<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Account page (routes/admin/account.php).
 *
 *   GET   /admin/account  the page (HTML), or the account as JSON when the request asks for JSON
 *   PATCH /admin/account  JSON only: name, email, contact number, password and photo
 *
 * Contract (resources/js/admin/account.js):
 *   200 {ok, message, admin{first_name, last_name, middle_initial, email, phone, avatar_url}}
 *   422 {message, errors{field: [msg]}}
 *   429 {message}  too many wrong current passwords
 *   401 {message}  signed out (there is no `auth` middleware on the admin side yet, so the
 *                  controller answers guests itself)
 *
 * The account IS the signed-in user (users table): nothing is ever deleted here, the rows are
 * only updated in place.
 */
class AccountController extends Controller
{
    /** Wrong "current password" answers allowed before 429 (same numbers as Account > Security). */
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 60;

    public function show(Request $request): View|JsonResponse
    {
        $user = $request->user();

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'admin' => $this->admin($user),
            ]);
        }

        $now = \Illuminate\Support\Carbon::now();

        return view('admin.account', [
            'admin' => $user, // null while nobody is signed in: the view falls back to sample values
            'lastLogin' => $now->copy()->subHours(3),
            'sessions' => [
                ['device' => 'Chrome on Windows', 'icon' => 'desktop', 'location' => 'Sample City, Bulacan', 'last_active' => $now, 'current' => true],
                ['device' => 'Safari on iPhone', 'icon' => 'device-mobile', 'location' => 'Sample City, Bulacan', 'last_active' => $now->copy()->subDay(), 'current' => false],
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Please sign in to manage your account.'], 401);
        }

        // The card only asks for the current password when the login (email) or the password
        // itself is being changed — the same rule the form applies (js/admin/account.js).
        $changesEmail = $request->filled('email');
        $changesPassword = filled($request->input('password'));
        $needsCurrentPassword = $changesEmail || $changesPassword;

        $limiterKey = 'admin-account:'.$user->id;

        if ($needsCurrentPassword && RateLimiter::tooManyAttempts($limiterKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($limiterKey);

            return response()->json([
                'message' => "Too many attempts. Please try again in {$seconds} seconds.",
            ], 429);
        }

        $validator = Validator::make($this->prepare($request->all()), [
            // `sometimes`: the form only posts the rows that were actually edited.
            'first_name' => ['sometimes', 'required', 'string', 'max:100', 'regex:/^[A-Za-z\s]+$/'],
            'last_name' => ['sometimes', 'required', 'string', 'max:100', 'regex:/^[A-Za-z\s]+$/'],
            'middle_initial' => ['sometimes', 'nullable', 'string', 'max:1', 'regex:/^[A-Za-z]$/'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['sometimes', 'nullable', 'string', 'regex:/^09\d{9}$/', Rule::unique('users', 'phone')->ignore($user->id)],
            'password' => ['sometimes', 'nullable', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'current_password' => [$needsCurrentPassword ? 'required' : 'sometimes', 'string'],
            'avatar' => ['sometimes', 'nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ], [
            'first_name.required' => 'First name is required.',
            'first_name.regex' => 'First name can only contain letters and spaces.',
            'last_name.required' => 'Last name is required.',
            'last_name.regex' => 'Last name can only contain letters and spaces.',
            'middle_initial.regex' => 'Middle initial must be a single letter.',
            'email.email' => 'Enter a valid email address.',
            'email.unique' => 'That email address is already used by another account.',
            'phone.regex' => 'Enter a valid mobile number (09 followed by 9 digits).',
            'phone.unique' => 'This contact number is already used by another account.',
            'password.confirmed' => 'Passwords do not match.',
            'current_password.required' => 'Enter your current password.',
            'avatar.mimes' => 'Use a JPG, PNG or WebP image.',
            'avatar.max' => 'The image must be 2 MB or smaller.',
        ]);

        if ($validator->fails()) {
            return $this->invalid($validator->errors()->toArray());
        }

        $validated = $validator->validated();

        if ($needsCurrentPassword && ! Hash::check((string) $request->input('current_password', ''), (string) $user->password)) {
            RateLimiter::hit($limiterKey, self::DECAY_SECONDS);

            return $this->invalid(['current_password' => ['Your current password is incorrect.']]);
        }

        if (array_key_exists('password', $validated) && filled($validated['password'])
            && Hash::check((string) $validated['password'], (string) $user->password)) {
            return $this->invalid(['password' => ['Choose a password different from your current one.']]);
        }

        $user->fill(collect($validated)->only([
            'first_name',
            'last_name',
            'middle_initial',
            'email',
            'phone',
        ])->all());

        if (array_key_exists('password', $validated) && filled($validated['password'])) {
            // The model's `hashed` cast hashes it. A new remember token ends "remember me" on
            // the other devices, same as Account > Security.
            $user->password = $validated['password'];
            $user->setRememberToken(Str::random(60));
        }

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');

            if (! $path) {
                return $this->invalid(['avatar' => ['The photo could not be uploaded. Please try again.']]);
            }

            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            $user->avatar_path = $path;
        }

        $user->save();

        if ($needsCurrentPassword) {
            RateLimiter::clear($limiterKey);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Your changes have been saved.',
            'admin' => $this->admin($user->refresh()),
        ]);
    }

    /**
     * Trims what the form sends as text. Keys the form did NOT send are left out entirely, so the
     * `sometimes` rules above only ever look at the rows that were edited.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepare(array $data): array
    {
        foreach (['first_name', 'last_name', 'email'] as $name) {
            if (array_key_exists($name, $data)) {
                $data[$name] = trim((string) $data[$name]);
            }
        }

        if (array_key_exists('middle_initial', $data)) {
            $data['middle_initial'] = strtoupper(trim((string) $data['middle_initial']));
        }

        if (array_key_exists('phone', $data)) {
            // '' means "cleared" on the form: store NULL, the column is nullable.
            $data['phone'] = preg_replace('/\D+/', '', (string) $data['phone']) ?: null;
        }

        return $data;
    }

    /** @param  array<string, string[]>  $errors */
    private function invalid(array $errors): JsonResponse
    {
        return response()->json([
            'message' => 'Please review the highlighted fields.',
            'errors' => $errors,
        ], 422);
    }

    /**
     * The shape the form writes back into its rows (js/admin/account.js applySaved).
     *
     * @return array{first_name: ?string, last_name: ?string, middle_initial: ?string, email: ?string, phone: ?string, avatar_url: ?string}
     */
    private function admin(?User $user): array
    {
        return [
            'first_name' => $user?->first_name,
            'last_name' => $user?->last_name,
            'middle_initial' => $user?->middle_initial,
            'email' => $user?->email,
            'phone' => $user?->phone,
            'avatar_url' => $user?->avatar_url,
        ];
    }
}
