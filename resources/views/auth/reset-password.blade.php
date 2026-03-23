@extends('layouts.app')

@section('title', 'Nuova password - Mokador ti porta in vacanza')

@section('content')
    @include('partials.teaser', ['title' => 'Nuova password'])

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
                <h2 class="section-title-dark">Imposta una nuova password</h2>
                <form action="{{ route('password.update') }}" method="POST" class="form-punti-vendita">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <input type="email" name="email" placeholder="Email" value="{{ old('email', $email) }}">
                    <input type="password" name="password" placeholder="Nuova password">
                    <input type="password" name="password_confirmation" placeholder="Conferma nuova password">
                    @include('partials.turnstile')
                    <button type="submit" class="btn-mokador">Reimposta password</button>
                </form>
                @endif
            </div>
        </div>
    </section>
@endsection
