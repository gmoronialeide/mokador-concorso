@extends('layouts.app')

@section('title', 'Gioca ora - Mokador ti porta in vacanza')

@push('head')
    <link href="https://cdn.jsdelivr.net/npm/slim-select@2.9.2/dist/slimselect.css" rel="stylesheet">
    <style>
        /* Slim Select — tema Mokador */
        .ss-main {
            border: 2px solid var(--brown-medium) !important;
            border-radius: 0 !important;
            padding: 0.55rem 0.85rem !important;
            min-height: auto !important;
            background-color: var(--white) !important;
            font-family: 'dm-sans', sans-serif !important;
            font-size: 1rem !important;
            color: var(--brown-extra-dark) !important;
        }
        .ss-main:focus-within {
            border-color: var(--orange) !important;
            box-shadow: none !important;
        }
        .ss-main .ss-arrow path {
            stroke: var(--brown-medium) !important;
        }
        .ss-main .ss-values .ss-placeholder {
            color: var(--brown-light) !important;
            font-style: normal !important;
        }
        .ss-main .ss-values .ss-single {
            color: var(--brown-extra-dark) !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        .ss-content {
            border: 2px solid var(--brown-medium) !important;
            border-radius: 0 !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
        }
        .ss-content .ss-search input {
            border: 2px solid var(--brown-extra-light) !important;
            border-radius: 0 !important;
            padding: 0.6rem !important;
            font-family: 'dm-sans', sans-serif !important;
            font-size: 0.95rem !important;
            color: var(--brown-extra-dark) !important;
        }
        .ss-content .ss-search input:focus {
            border-color: var(--orange) !important;
            box-shadow: none !important;
        }
        .ss-content .ss-list .ss-option {
            padding: 0.6rem 0.85rem !important;
            font-size: 0.95rem !important;
            color: var(--brown-extra-dark) !important;
        }
        .ss-content .ss-list .ss-option:hover,
        .ss-content .ss-list .ss-option.ss-highlighted {
            background-color: var(--brown-extra-light) !important;
            color: var(--brown-dark) !important;
        }
        .ss-content .ss-list .ss-option.ss-selected {
            background-color: var(--orange) !important;
            color: var(--white) !important;
        }
        .ss-content .ss-list .ss-option .ss-search-highlight {
            background-color: rgba(157, 74, 21, 0.2) !important;
        }
    </style>
@endpush

@section('content')
    @include('partials.teaser', ['title' => 'Gioca ora'])

    <section>
        <div class="container pb-5">
            <div class="first-section">
                <h2 class="section-title-dark">{{ $alreadyPlayed ? 'Hai già giocato oggi' : 'Scopri subito se hai vinto' }}</h2>

                @if (! $contestActive)
                    <p class="text-subtitle text-center">Il concorso è attivo dal <strong>{{ \Carbon\Carbon::parse(config('app.concorso_start_date'))->format('d/m/Y') }}</strong> al <strong>{{ \Carbon\Carbon::parse(config('app.concorso_end_date'))->format('d/m/Y') }}</strong>.<br>Torna nel periodo del concorso per giocare!</p>
                @elseif ($alreadyPlayed)
                    <p class="text-subtitle text-center">Torna domani per tentare di nuovo la fortuna!</p>
                @else
                    <p class="text-subtitle text-center">Indica il punto vendita, carica lo scontrino e conservalo per l'estrazione finale!</p>
                    <form action="{{ route('game.play') }}" method="POST" enctype="multipart/form-data" class="form-punti-vendita">
                        @csrf
                        <div class="form-register-col">
                            <label for="punto-vendita">Punto vendita</label>
                            <select id="punto-vendita" name="store_id" required>
                                <option value="" data-placeholder="true">Cerca punto vendita...</option>
                                @foreach ($stores as $store)
                                    <option value="{{ $store->id }}" @selected((int) old('store_id') === $store->id)>{{ $store->code }} — {{ $store->display_name }} — {{ $store->city }} ({{ $store->province }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-register-col">
                            <label for="scontrino">Carica lo scontrino</label>
                            <input type="file" id="scontrino" name="receipt" accept="image/jpeg,image/png" required>
                            <p class="small-caption mt-2 mb-0">Si prega di fotografare lo scontrino nella sua interezza, senza nascondere il nome del punto vendita e la data.</p>
                        </div>
                        <button type="submit" class="btn-mokador mt-auto">Gioca</button>
                    </form>
                @endif
            </div>
        </div>
    </section>
@endsection

@push('scripts')
@if (! ($alreadyPlayed ?? false) && ($contestActive ?? false))
<script src="https://cdn.jsdelivr.net/npm/slim-select@2.9.2/dist/slimselect.min.js"></script>
<script>
    new SlimSelect({
        select: '#punto-vendita',
        settings: {
            searchText: 'Nessun risultato',
            searchPlaceholder: 'Cerca per nome, città o codice...',
            searchHighlight: true,
            allowDeselect: true,
        }
    });
</script>
@endif
@endpush
