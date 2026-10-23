<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule; 
use Illuminate\View\View;

/**
 * Account > Profile.
 *
 *   GET   user.account.profile         the page (HTML), or the profile as JSON when the request asks for JSON
 *   PATCH user.account.profile.update  JSON only
 *
 * Contract (resources/js/account/profile.js):
 *   200 {ok, message, profile{first_name, middle_name, middle_initial, last_name, email, phone, address}}
 *   422 {message, errors{field: [msg]}}
 *   401 {message}  (the `auth` middleware)
 */
class AccountProfileController extends Controller
{
    public function show(Request $request): View|JsonResponse
    {
        $user = $request->user();

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'profile' => $this->profile($user) + [
                    'email_verified' => $user->email_verified_at !== null,
                    'member_since' => $user->created_at?->toDateString(),
                ],
            ]);
        }

        return view('user.account.profile', ['user' => $user]);
    }

    public function update(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // The form sends '' for an empty optional field: treat it as "not given".
        $input = [
            'first_name' => trim((string) $request->input('first_name', '')),
            'last_name' => trim((string) $request->input('last_name', '')),
            'phone' => preg_replace('/\s+/', '', (string) $request->input('phone', '')) ?: null,
            'address' => trim((string) $request->input('address', '')) ?: null,
        ];

        // The profile form posts middle_name; middle_initial is still accepted so older
        // clients (and the admin-shaped payloads) keep working.
        if ($request->exists('middle_name')) {
            $input['middle_name'] = trim((string) $request->input('middle_name')) ?: null;
        }
        if ($request->exists('middle_initial')) {
            $input['middle_initial'] = strtoupper(trim((string) $request->input('middle_initial'))) ?: null;
        }

        $validator = Validator::make($input, [
            'first_name' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z\s]+$/'],
            'middle_name' => ['sometimes', 'nullable', 'string', 'max:100', 'regex:/^[A-Za-z\s]+$/'],
            'middle_initial' => ['sometimes', 'nullable', 'string', 'max:1', 'regex:/^[A-Za-z]$/'],
            'last_name' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z\s]+$/'],
            'phone' => ['nullable', 'regex:/^09\d{9}$/', Rule::unique('users', 'phone')->ignore($user->id)],
            'address' => ['nullable', 'string', 'max:1000', 'not_regex:/[\r\n]/'],
        ], [
            'first_name.required' => 'First name is required.',
            'first_name.regex' => 'First name can only contain letters and spaces.',
            'middle_name.regex' => 'Middle name can only contain letters and spaces.',
            'middle_initial.regex' => 'Middle initial must be a single letter.',
            'last_name.required' => 'Last name is required.',
            'last_name.regex' => 'Last name can only contain letters and spaces.',
            'phone.regex' => 'Enter a valid mobile number (09 followed by 9 digits).',
            'phone.unique' => 'This contact number is already used by another account.',
            'address.not_regex' => 'Address must be on a single line.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Please review the highlighted fields.',
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        $validated = $validator->validated();

        // Email is the login and is never changed here, so it is not accepted from the request.
        $user->fill(collect($validated)->except(['middle_name', 'middle_initial'])->all());

        if (array_key_exists('middle_name', $validated)) {
            // Stores the middle name and derives middle_initial from its first letter.
            $user->setMiddleName($validated['middle_name']);
        } elseif (array_key_exists('middle_initial', $validated)) {
            // Legacy payload: a lone initial is kept in both columns.
            $user->setMiddleName($validated['middle_initial']);
        }

        $user->save();

        return response()->json([
            'ok' => true,
            'message' => 'Your profile has been updated.',
            'profile' => $this->profile($user->refresh()),
        ]);
    }

    private function profile(User $user): array
    {
        return [
            'first_name' => $user->first_name,
            'middle_name' => $user->middle_name,
            'middle_initial' => $user->middle_initial,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'address' => $user->address,
        ];
    }
}
