<?php

namespace App\Http\Controllers;

use Coderflex\LaravelTurnstile\Rules\TurnstileCheck;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function requestForm(): View
    {
        return view('auth.forgot-password', $this->contestStatus());
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $status = $this->contestStatus();

        if ($status['contestNotStarted'] || $status['contestEnded']) {
            return redirect()->route('home');
        }

        $request->validate([
            'email' => ['required', 'email'],
            'cf-turnstile-response' => ['required', new TurnstileCheck],
        ], [
            'email.required' => 'Inserisci la tua email.',
            'email.email' => 'Inserisci un indirizzo email valido.',
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('success', 'Ti abbiamo inviato un\'email con il link per reimpostare la password.');
        }

        return back()->withErrors(['email' => 'Non abbiamo trovato un account con questa email.']);
    }

    public function resetForm(Request $request, string $token): View
    {
        return view('auth.reset-password', array_merge(
            $this->contestStatus(),
            ['token' => $token, 'email' => $request->query('email', '')]
        ));
    }

    public function reset(Request $request): RedirectResponse
    {
        $status = $this->contestStatus();

        if ($status['contestNotStarted'] || $status['contestEnded']) {
            return redirect()->route('home');
        }

        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->mixedCase()->numbers()],
            'cf-turnstile-response' => ['required', new TurnstileCheck],
        ], [
            'password.confirmed' => 'Le password non corrispondono.',
        ]);

        $resetStatus = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                if (! $user->hasVerifiedEmail()) {
                    $user->markEmailAsVerified();
                }
            }
        );

        if ($resetStatus === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('success', 'Password reimpostata! Puoi accedere con la nuova password.');
        }

        return back()->withErrors(['email' => __($resetStatus)]);
    }
}
