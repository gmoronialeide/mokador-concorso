@extends('layouts.app')

@section('title', 'Non hai vinto - Mokador ti porta in vacanza')

@section('content')
    @include('partials.teaser', ['title' => 'Gioca ora'])

    <section>
        <div class="container pb-5">
            <div class="first-section">
                <h2 class="section-title-dark">Non hai vinto</h2>
                <p class="text-subtitle text-center">Mi dispiace, non hai vinto.<br>
                    Potrai ritentare con un'altra volta domani.</p>
                <h4 class="text-center">Grazie per aver partecipato al concorso!</h4>
            </div>
        </div>
    </section>
@endsection
