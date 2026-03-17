@extends('layouts.app')

@section('title', '404 - Mokador ti porta in vacanza')

@section('content')
    @include('partials.teaser', ['title' => 'Pagina non trovata'])

    <section>
        <div class="container">
            <div class="d-flex flex-column align-items-center gap-3 py-5">
                <h2 class="text-large"><strong>404</strong></h2>
                <h3>La pagina che stai cercando non esiste</h3>
                <a href="{{ route('home') }}" class="btn-mokador">Torna alla home</a>
            </div>
        </div>
    </section>
@endsection
