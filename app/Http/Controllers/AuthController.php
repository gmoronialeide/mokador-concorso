<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            return back()->withErrors(['email' => 'Credenziali non valide.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        $user = Auth::user();

        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return redirect()->intended(route('game.show'));
    }

    public function logout(): RedirectResponse
    {
        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('home');
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $validated = $request->safe()->except(['cf-turnstile-response', 'password_confirmation', 'privacy_consent']);
        $validated['marketing_consent'] = $request->boolean('marketing_consent');
        $validated['privacy_consent'] = true;

        $plainPassword = $validated['password'];

        $user = User::create($validated);

        $user->plainPassword = $plainPassword;
        event(new Registered($user));

        return redirect()->route('login')->with('success', 'Registrazione completata! Controlla la tua email per confermare l\'account.');
    }

    public function verificationNotice(): View
    {
        return view('auth.verify-email');
    }

    public function verificationResend(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('game.show');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', 'Email di verifica inviata!');
    }
}
