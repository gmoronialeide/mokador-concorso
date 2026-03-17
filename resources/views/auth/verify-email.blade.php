@extends('layouts.app')

@section('title', 'Verifica email - Mokador ti porta in vacanza')

@section('content')
    @include('partials.teaser', ['title' => 'Verifica email'])

    <section>
        <div class="container pb-5">
            <div class="first-section">
                <h2 class="section-title-dark">Controlla la tua email</h2>
                <p class="text-subtitle text-center">Ti abbiamo inviato un'email di verifica all'indirizzo <strong>{{ auth()->user()->email }}</strong>.<br>
                    Clicca sul link nell'email per attivare il tuo account e iniziare a giocare.</p>
                <p class="text-subtitle text-center">Non hai ricevuto l'email?</p>
                <form action="{{ route('verification.resend') }}" method="POST" class="d-flex justify-content-center">
                    @csrf
                    <button type="submit" class="btn-mokador">Reinvia email di verifica</button>
                </form>
            </div>
        </div>
    </section>
@endsection
