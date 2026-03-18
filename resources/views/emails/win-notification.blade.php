@extends('emails.layouts.base')

@section('body')
    <h2 style="color: #4F3328; text-align: center;">Complimenti {{ $user->name }}!</h2>
    <p style="color: #614637; font-size: 16px; text-align: center;">Hai vinto:</p>
    <div style="background-color: #DFD6C1; padding: 20px; border-radius: 8px; text-align: center; margin: 20px 0;">
        <h3 style="color: #4F3328; margin: 0;">{{ $prize->name }}</h3>
    </div>
    <p style="color: #614637; font-size: 14px;">Per ricevere il tuo premio dovrai inviare una copia dei tuoi documenti <strong>entro 5 giorni</strong> a <a href="mailto:concorso@mokador.it" style="color: #9D4A15;">concorso@mokador.it</a>.</p>
    <p style="color: #614637; font-size: 14px;">Ricordati di <strong>conservare lo scontrino originale</strong>: ti verrà richiesto per la convalida del premio.</p>
@endsection
