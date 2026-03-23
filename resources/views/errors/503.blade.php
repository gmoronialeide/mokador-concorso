@extends('layouts.app')

@section('title', 'Manutenzione - Mokador ti porta in vacanza')

@section('content')
    @include('partials.teaser', ['title' => 'Manutenzione in corso'])

    <section>
        <div class="container">
            <div class="d-flex flex-column align-items-center gap-3 py-5">
                <h2 class="text-large"><strong>503</strong></h2>
                <h3>Il sito è in manutenzione</h3>
                <p>Torneremo online a breve. Grazie per la pazienza!</p>
            </div>
        </div>
    </section>
@endsection
