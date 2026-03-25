@extends('layouts.app')

@section('title', 'Login - Mokador ti porta in vacanza')

@section('content')
    @include('partials.teaser', ['title' => 'Login'])

    <section>
        <div class="container">
            <div class="first-section">
                @if ($contestEnded)
                    <div class="d-flex flex-column align-items-center gap-3 py-5">
                        <h2 class="section-title-dark">Il concorso è concluso</h2>
                        <p>Grazie per aver partecipato!</p>
                        <a href="{{ route('home') }}" class="btn-mokador">Torna alla home</a>
                    </div>
                @elseif ($contestNotStarted)
                    <div class="d-flex flex-column align-items-center gap-3 py-5">
                        <h2 class="section-title-dark">Il concorso non è ancora iniziato</h2>
                        <p>Sarà possibile accedere dal {{ $startDate->format('d/m/Y') }}.</p>
                        <a href="{{ route('home') }}" class="btn-mokador">Torna alla home</a>
                    </div>
                @else
                <h2 class="section-title-dark">Accedi per giocare</h2>
                <p class="text-subtitle text-center">Se hai già un account, accedi per tentare la fortuna!<br>
                    Se non hai un account puoi <a href="{{ route('register') }}">registrarti qui</a>.</p>
                <form action="{{ route('login') }}" method="POST" class="form-punti-vendita">
                    @csrf
                    <input type="email" name="email" placeholder="Email" value="{{ old('email') }}" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <button type="submit" class="btn-mokador">Accedi</button>
                </form>
                <p class="text-subtitle text-center"><a href="{{ route('password.request') }}">Password dimenticata?</a></p>
                <p class="text-subtitle text-center pb-4">Non hai un account? <a href="{{ route('register') }}" class="btn-mokador ms-0 mt-2 mt-md-0 ms-md-3">Registrati</a></p>
                @endif
            </div>
        </div>
    </section>
@endsection
