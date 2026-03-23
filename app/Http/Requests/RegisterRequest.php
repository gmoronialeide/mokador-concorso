<?php

namespace App\Http\Requests;

use Coderflex\LaravelTurnstile\Rules\TurnstileCheck;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->phone) {
            $phone = preg_replace('/\s+/', '', $this->phone);
            $phone = preg_replace('/^\+39/', '', $phone);
            $this->merge(['phone' => $phone]);
        }
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'surname' => ['required', 'string', 'max:100'],
            'birth_date' => ['required', 'date', 'before:-18 years'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^3\d{8,9}$/', 'unique:users,phone'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['required', 'string', 'size:2'],
            'cap' => ['required', 'string', 'size:5', 'regex:/^\d{5}$/'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
            'privacy_consent' => ['accepted'],
            'marketing_consent' => ['nullable', 'boolean'],
            'cf-turnstile-response' => ['required', new TurnstileCheck()],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'birth_date.before' => 'Devi essere maggiorenne per partecipare.',
            'email.unique' => 'Questa email è già registrata.',
            'privacy_consent.accepted' => 'Devi accettare la privacy policy per registrarti.',
            'password.confirmed' => 'Le password non corrispondono.',
            'province.size' => 'La provincia deve essere di 2 caratteri (es. BO, MI).',
            'cap.size' => 'Il CAP deve essere di 5 cifre.',
            'cap.regex' => 'Il CAP deve contenere solo 5 cifre numeriche.',
            'phone.regex' => 'Inserisci un numero di cellulare italiano valido (es. 3331234567).',
            'phone.unique' => 'Questo numero di telefono è già registrato.',
            'cf-turnstile-response.required' => 'Completa la verifica captcha.',
        ];
    }
}
