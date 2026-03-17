<?php

namespace App\Http\Requests;

use Coderflex\LaravelTurnstile\Rules\TurnstileCheck;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'cf-turnstile-response' => ['required', new TurnstileCheck()],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'email.required' => 'Inserisci la tua email.',
            'email.email' => 'Inserisci un indirizzo email valido.',
            'password.required' => 'Inserisci la password.',
            'cf-turnstile-response.required' => 'Completa la verifica captcha.',
        ];
    }
}
