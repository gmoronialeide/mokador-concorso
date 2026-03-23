@extends('layouts.app')

@section('title', 'Errore - Mokador ti porta in vacanza')

@section('content')
    @include('partials.teaser', ['title' => 'Errore del server'])

    <section>
        <div class="container">
            <div class="d-flex flex-column align-items-center gap-3 py-5">
                <h2 class="text-large"><strong>500</strong></h2>
                <h3>Si è verificato un errore imprevisto</h3>
                <p>Stiamo lavorando per risolvere il problema. Riprova tra qualche minuto.</p>
                <a href="{{ route('home') }}" class="btn-mokador">Torna alla home</a>
            </div>
        </div>
    </section>
@endsection
