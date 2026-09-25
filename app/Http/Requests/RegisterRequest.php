<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name'  => ['required', 'string', 'max:255'],
            'identifier' => ['required', 'string', 'max:255'],
            'email'      => [
                'required_without:phone',
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->whereNotNull('email_verified_at'),
            ],
            'phone'      => ['required_without:email', 'nullable', 'string', 'max:20', 'unique:users,phone'],
            'password'   => ['required', Password::min(8)->mixedCase()->numbers()],
            'terms'      => ['accepted'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('identifier')) {
            $identifier = trim((string) $this->input('identifier'));

            if (str_contains($identifier, '@')) {
                $this->merge([
                    'email' => strtolower($identifier),
                    'phone' => null,
                ]);
            } else {
                $this->merge([
                    'phone' => preg_replace('/\D+/', '', $identifier),
                    'email' => null,
                ]);
            }
        }

        if ($this->filled('email')) {
            $this->merge(['email' => strtolower(trim($this->email))]);
        }
        if ($this->filled('phone')) {
            $this->merge(['phone' => trim($this->phone)]);
        }
    }

    public function messages(): array
    {
        return [
            'email.required_without' => 'Please provide an email or a phone number.',
            'phone.required_without' => 'Please provide an email or a phone number.',
        ];
    }
}
