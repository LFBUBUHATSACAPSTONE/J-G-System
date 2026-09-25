<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
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
            'email'      => ['required_without:phone', 'nullable', 'email', 'max:255', 'unique:users,email'],
            'phone'      => ['required_without:email', 'nullable', 'string', 'max:20', 'unique:users,phone'],
            'password'   => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ];
    }

    protected function prepareForValidation(): void
    {
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
