@extends('layouts.app')

@section('title', 'Registrazione completata - Mokador ti porta in vacanza')

@section('content')
    <section>
        <div class="container">
            <div class="d-flex flex-column align-items-center justify-content-center text-center" style="min-height: 50vh;">
                <div class="d-flex flex-column align-items-center gap-3 p-4">
                    <h2 class="section-title-dark" style="color: #28a745;">Registrazione completata!</h2>
                    <p class="text-subtitle">Il tuo account è stato creato con successo.<br>Controlla la tua email per confermare l'account e iniziare a giocare!</p>
                    <a href="{{ route('login') }}" class="btn-mokador" style="position: relative;">Vai al login</a>
                </div>
            </div>
        </div>
    </section>
@endsection
