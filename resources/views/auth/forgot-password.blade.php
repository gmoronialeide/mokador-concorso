@extends('layouts.app')

@section('title', 'Password dimenticata - Mokador ti porta in vacanza')

@section('content')
    @include('partials.teaser', ['title' => 'Password dimenticata'])

    <section>
        <div class="container">
            <div class="first-section">
                <h2 class="section-title-dark">Recupera la password</h2>
                <p class="text-subtitle text-center">Inserisci la tua email e ti invieremo un link per reimpostare la password.</p>
                <form action="{{ route('password.email') }}" method="POST" class="form-punti-vendita">
                    @csrf
                    <input type="email" name="email" placeholder="Email" value="{{ old('email') }}">
                    @include('partials.turnstile')
                    <button type="submit" class="btn-mokador">Invia link di recupero</button>
                </form>
                <p class="text-subtitle text-center"><a href="{{ route('login') }}">Torna al login</a></p>
            </div>
        </div>
    </section>
@endsection
