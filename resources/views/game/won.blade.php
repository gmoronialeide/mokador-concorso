@extends('layouts.app')

@section('title', 'Hai vinto! - Mokador ti porta in vacanza')

@section('content')
    @include('partials.teaser', ['title' => 'Gioca ora'])

    <section>
        <div class="container pb-5">
            <div class="first-section">
                <h2 class="section-title-dark">Hai vinto!</h2>
                <div class="win-card">
                    <h3>{{ $prize->name }}</h3>
                    @if ($prize->image)
                        <img src="{{ asset('img/' . $prize->image) }}" alt="{{ $prize->name }}">
                    @endif
                </div>
                <p class="text-subtitle text-center">A breve riceverai una <strong>mail</strong> con le istruzioni per
                    ricevere il tuo <strong>premio</strong>.<br>
                    Invia una copia del tuo documento di identità <strong>entro 5 giorni</strong> da ora per completare il
                    processo di consegna.</p>
                <h4 class="text-center">Grazie per aver partecipato al concorso!</h4>
            </div>
        </div>
    </section>
@endsection