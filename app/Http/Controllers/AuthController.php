<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\Province;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login', $this->contestStatus());
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $status = $this->contestStatus();

        if ($status['contestNotStarted'] || $status['contestEnded']) {
            return redirect()->route('home');
        }

        if (! Auth::attempt($request->only('email', 'password'))) {
            return back()->withErrors(['email' => 'Credenziali non valide.'])->onlyInput('email');
        }

        $user = Auth::user();

        $request->session()->regenerate();

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
        $provinces = Province::orderBy('name')->get();

        return view('auth.register', array_merge(
            $this->contestStatus(),
            compact('provinces')
        ));
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $status = $this->contestStatus();

        if ($status['contestNotStarted']) {
            return back()->with('error', 'Le iscrizioni non sono ancora aperte.');
        }

        if ($status['contestEnded']) {
            return back()->with('error', 'Il concorso è concluso.');
        }

        $validated = $request->safe()->except(['cf-turnstile-response', 'password_confirmation', 'privacy_consent']);
        $validated['marketing_consent'] = $request->boolean('marketing_consent');
        $validated['privacy_consent'] = true;

        $plainPassword = $validated['password'];

        $user = User::create($validated);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('register.success');
    }

    public function registerSuccess(): View
    {
        return view('auth.registered');
    }
}
