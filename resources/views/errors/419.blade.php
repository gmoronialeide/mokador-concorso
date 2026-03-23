@extends('layouts.app')

@section('title', 'Sessione scaduta - Mokador ti porta in vacanza')

@section('content')
    @include('partials.teaser', ['title' => 'Sessione scaduta'])

    <section>
        <div class="container">
            <div class="d-flex flex-column align-items-center gap-3 py-5">
                <h2 class="text-large"><strong>419</strong></h2>
                <h3>La sessione è scaduta</h3>
                <p>Per motivi di sicurezza la sessione è scaduta. Ricarica la pagina e riprova.</p>
                <a href="{{ route('home') }}" class="btn-mokador">Torna alla home</a>
            </div>
        </div>
    </section>
@endsection
