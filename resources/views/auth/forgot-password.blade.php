@extends('layouts.app')

@section('title', 'Password dimenticata - Mokador ti porta in vacanza')

@section('content')
    @include('partials.teaser', ['title' => 'Password dimenticata'])

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
                <h2 class="section-title-dark">Recupera la password</h2>
                <p class="text-subtitle text-center">Inserisci la tua email e ti invieremo un link per reimpostare la password.</p>
                <form action="{{ route('password.email') }}" method="POST" class="form-punti-vendita">
                    @csrf
                    <input type="email" name="email" placeholder="Email" value="{{ old('email') }}">
                    @include('partials.turnstile')
                    <button type="submit" class="btn-mokador">Invia</button>
                </form>
                <p class="text-subtitle text-center"><a href="{{ route('login') }}">Torna al login</a></p>
                @endif
            </div>
        </div>
    </section>
@endsection
