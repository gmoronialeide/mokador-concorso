<?php

namespace App\Filament\Pages\Auth;

use Coderflex\LaravelTurnstile\Rules\TurnstileCheck;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Illuminate\Validation\ValidationException;

class Login extends \Filament\Auth\Pages\Login
{
    protected string $view = 'filament.pages.login';

    public ?string $turnstileToken = null;

    public function authenticate(): ?LoginResponse
    {
        $validator = validator(
            ['cf-turnstile-response' => $this->turnstileToken],
            ['cf-turnstile-response' => ['required', new TurnstileCheck()]],
            ['cf-turnstile-response.required' => 'Completa la verifica captcha.']
        );

        if ($validator->fails()) {
            throw ValidationException::withMessages([
                'data.email' => $validator->errors()->first('cf-turnstile-response'),
            ]);
        }

        session()->forget('url.intended');

        return parent::authenticate();
    }
}
