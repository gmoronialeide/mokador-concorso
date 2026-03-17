@extends('layouts.app')

@section('title', 'Gioca ora - Mokador ti porta in vacanza')

@section('content')
    @include('partials.teaser', ['title' => 'Gioca ora'])

    <section>
        <div class="container pb-5">
            <div class="first-section">
                <h2 class="section-title-dark">Scopri subito se hai vinto</h2>

                @if (! $contestActive)
                    <p class="text-subtitle text-center">Il concorso è attivo dal <strong>{{ \Carbon\Carbon::parse(config('app.concorso_start_date'))->format('d/m/Y') }}</strong> al <strong>{{ \Carbon\Carbon::parse(config('app.concorso_end_date'))->format('d/m/Y') }}</strong>.<br>Torna nel periodo del concorso per giocare!</p>
                @elseif ($alreadyPlayed)
                    <p class="text-subtitle text-center">Hai già giocato oggi.<br>Torna domani per tentare di nuovo la fortuna!</p>
                @else
                    <p class="text-subtitle text-center">Indica il codice del punto vendita, carica lo scontrino e conservalo per l'estrazione finale!</p>
                    <form action="{{ route('game.play') }}" method="POST" enctype="multipart/form-data" class="form-punti-vendita">
                        @csrf
                        <div class="form-register-col">
                            <label for="punto-vendita">Numero del punto vendita</label>
                            <input type="text" id="punto-vendita" name="store_code" placeholder="1234" value="{{ old('store_code') }}" required>                        </div>
                        <div class="form-register-col">
                            <label for="scontrino">Carica lo scontrino</label>
                            <input type="file" id="scontrino" name="receipt" accept="image/jpeg,image/png" required>                        </div>
                        <button type="submit" class="btn-mokador mt-auto">Gioca</button>
                    </form>
                @endif
            </div>
        </div>
    </section>
@endsection
