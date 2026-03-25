@extends('layouts.app')

@section('title', 'Registrati - Mokador ti porta in vacanza')

@section('content')
    @include('partials.teaser', ['title' => 'Registrati'])

    <section>
        <div class="container">
            <div class="first-section">
                @if ($contestEnded)
                    <div class="d-flex flex-column align-items-center gap-3 py-5">
                        <h2 class="section-title-dark">Il concorso è concluso</h2>
                        <p>Grazie per aver partecipato!</p>
                        <a href="{{ route('home') }}" class="btn-mokador">Torna alla home</a>
                    </div>
                @elseif ($contestNotStarted)
                    <div class="d-flex flex-column align-items-center gap-3 py-5">
                        <h2 class="section-title-dark">Registrazioni non ancora aperte</h2>
                        <p>Sarà possibile iscriversi dal {{ $startDate->format('d/m/Y') }}.</p>
                        <a href="{{ route('home') }}" class="btn-mokador">Torna alla home</a>
                    </div>
                @else
                <h2 class="section-title-dark">Registrati e gioca subito</h2>
                <form action="{{ route('register') }}" method="POST" class="form-register">
                    @csrf
                    <div class="form-register-row">
                        <div class="form-register-col">
                            <label for="nome">Nome<span>*</span></label>
                            <input type="text" id="nome" name="name" placeholder="Nome" value="{{ old('name') }}" required>                        </div>
                        <div class="form-register-col">
                            <label for="cognome">Cognome<span>*</span></label>
                            <input type="text" id="cognome" name="surname" placeholder="Cognome" value="{{ old('surname') }}" required>                        </div>
                        <div class="form-register-col">
                            <label for="data-di-nascita">Data di nascita<span>*</span></label>
                            <input type="date" id="data-di-nascita" name="birth_date" value="{{ old('birth_date') }}" required>                        </div>
                        <div class="form-register-col">
                            <label for="indirizzo">Indirizzo<span>*</span></label>
                            <input type="text" id="indirizzo" name="address" placeholder="Indirizzo" value="{{ old('address') }}" required>                        </div>
                    </div>
                    <div class="form-register-row">
                        <div class="form-register-col">
                            <label for="citta">Città<span>*</span></label>
                            <input type="text" id="citta" name="city" placeholder="Città" value="{{ old('city') }}" required>                        </div>
                        <div class="form-register-col">
                            <label for="provincia">Provincia<span>*</span></label>
                            <select id="provincia" name="province" required>
                                <option value="">Seleziona</option>
                                @foreach($provinces as $province)
                                    <option value="{{ $province->code }}" @selected(old('province') === $province->code)>{{ $province->code }} - {{ $province->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-register-col">
                            <label for="cap">CAP<span>*</span></label>
                            <input type="text" id="cap" name="cap" placeholder="CAP" value="{{ old('cap') }}" maxlength="5" required>                        </div>
                        <div class="form-register-col">
                            <label for="cellulare">Cellulare<span>*</span></label>
                            <input type="tel" id="cellulare" name="phone" placeholder="Cellulare" value="{{ old('phone') }}" required>                        </div>
                    </div>
                    <div class="form-register-row">
                        <div class="form-register-col">
                            <label for="email">Email<span>*</span></label>
                            <input type="email" id="email" name="email" placeholder="Email" value="{{ old('email') }}" required>                        </div>
                        <div class="form-register-col">
                            <label for="password">Password<span>*</span></label>
                            <input type="password" id="password" name="password" placeholder="Password" required>                        </div>
                        <div class="form-register-col">
                            <label for="conferma-password">Conferma Password<span>*</span></label>
                            <input type="password" id="conferma-password" name="password_confirmation" placeholder="Conferma Password" required>
                        </div>
                    </div>
                    <div class="w-100 d-flex justify-content-center align-items-center gap-2 flex-column">
                        <div class="checkbox-policy">
                            <input type="checkbox" id="privacy-consent" name="privacy_consent" value="1" {{ old('privacy_consent') ? 'checked' : '' }}>
                            <label for="privacy-consent">Esprimo il consenso al trattamento dei miei dati personali (es. raccolta archiviazione, conservazione, consultazione, etc.). <a href="{{ route('privacy') }}" target="_blank" rel="noopener">Leggi l'informativa</a><span>*</span></label>
                        </div>                        <div class="checkbox-policy">
                            <input type="checkbox" id="marketing-consent" name="marketing_consent" value="1" {{ old('marketing_consent') ? 'checked' : '' }}>
                            <label for="marketing-consent">Esprimo il consenso al trattamento dei miei dati personali per le finalità promozionali, pubblicitarie e di marketing.</label>
                        </div>
                    </div>
                    <p class="small-caption text-center w-100 m-0">* campi obbligatori</p>
                    @include('partials.turnstile')
                    <button type="submit" class="btn-mokador">Registrati</button>
                </form>
                @endif
            </div>
        </div>
    </section>

    @if (app()->isLocal() && !$contestNotStarted && !$contestEnded)
        @include('partials.dev-autofill')
    @endif
@endsection
