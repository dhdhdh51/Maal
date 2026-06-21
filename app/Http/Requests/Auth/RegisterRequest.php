<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'age_confirm' => ['accepted'],
            'terms' => ['accepted'],
            'referral_code' => ['nullable', 'string', 'max:32', 'exists:users,referral_code'],
        ];
    }

    public function messages(): array
    {
        return [
            'age_confirm.accepted' => 'You must confirm you meet the minimum age requirement.',
            'terms.accepted' => 'You must accept the Terms and Privacy Policy.',
            'referral_code.exists' => 'That referral code is not valid.',
        ];
    }
}
