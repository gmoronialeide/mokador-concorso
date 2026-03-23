<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\Province;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

        $user = Auth::user();

        if (! $user->hasVerifiedEmail()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['email' => 'Devi confermare la tua email prima di accedere. Controlla la tua casella di posta.'])->onlyInput('email');
        }

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
        $now = Carbon::now();
        $startDate = Carbon::parse(config('app.concorso_start_date'));
        $endDate = Carbon::parse(config('app.concorso_end_date'));
        $contestNotStarted = $now->lt($startDate);
        $contestEnded = $now->gt($endDate->endOfDay());

        return view('auth.register', compact('provinces', 'contestNotStarted', 'contestEnded', 'startDate'));
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $now = Carbon::now();
        $startDate = Carbon::parse(config('app.concorso_start_date'));
        $endDate = Carbon::parse(config('app.concorso_end_date'));

        if ($now->lt($startDate)) {
            return back()->with('error', 'Le iscrizioni non sono ancora aperte.');
        }

        if ($now->gt($endDate->endOfDay())) {
            return back()->with('error', 'Il concorso è concluso.');
        }

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
