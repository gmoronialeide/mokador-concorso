@extends('emails.layouts.base')

@section('body')
    <h2 style="color: #4F3328; text-align: center; margin-top: 0;">Recupero password</h2>
    <p style="color: #614637; font-size: 16px; text-align: center;">Hai richiesto di reimpostare la password del tuo account.</p>

    <p style="color: #614637; font-size: 16px; text-align: center;">Clicca sul pulsante qui sotto per scegliere una nuova password:</p>

    <div style="text-align: center; margin: 28px 0;">
        <a href="{{ $resetUrl }}" style="display: inline-block; background-color: #9D4A15; color: #ffffff; padding: 14px 32px; text-decoration: none; border-radius: 4px; font-size: 16px; font-weight: bold;">Reimposta password</a>
    </div>

    <p style="color: #999; font-size: 13px; text-align: center;">Questo link scadrà tra {{ $expireMinutes }} minuti.</p>

    <p style="color: #999; font-size: 13px; text-align: center;">Se non riesci a cliccare il pulsante, copia e incolla questo link nel browser:</p>
    <p style="color: #9D4A15; font-size: 12px; text-align: center; word-break: break-all;">{{ $resetUrl }}</p>

    <hr style="border: none; border-top: 1px solid #f0ebe0; margin: 24px 0;">

    <p style="color: #999; font-size: 13px; text-align: center;">Se non hai richiesto il recupero password, ignora questa email.</p>
@endsection
