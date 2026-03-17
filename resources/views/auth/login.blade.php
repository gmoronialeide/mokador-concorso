@extends('layouts.app')

@section('title', 'Login - Mokador ti porta in vacanza')

@section('content')
    @include('partials.teaser', ['title' => 'Login'])

    <section>
        <div class="container">
            <div class="first-section">
                <h2 class="section-title-dark">Accedi per giocare</h2>
                <p class="text-subtitle text-center">Se hai già un account, accedi per tentare la fortuna!<br>
                    Se non hai un account puoi <a href="{{ route('register') }}">registrarti qui</a>.</p>
                <form action="{{ route('login') }}" method="POST" class="form-punti-vendita">
                    @csrf
                    <input type="email" name="email" placeholder="Email" value="{{ old('email') }}" required>
                    <input type="password" name="password" placeholder="Password" required>
                    @include('partials.turnstile')
                    <button type="submit" class="btn-mokador">Accedi</button>
                </form>
                <p class="text-subtitle text-center"><a href="{{ route('password.request') }}">Password dimenticata?</a></p>
                <p class="text-subtitle text-center">Non hai un account? <a href="{{ route('register') }}" class="btn-mokador ms-0 mt-3 mt-md-0 ms-md-3">Registrati</a></p>
            </div>
        </div>
    </section>
@endsection
