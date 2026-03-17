@extends('layouts.app')

@section('title', 'Punti Vendita - Mokador ti porta in vacanza')

@section('content')
    @include('partials.teaser', ['title' => 'Punti Vendita'])

    <section>
        <div class="container">
            <div class="first-section">
                <h2 class="section-title-dark">Dove posso partecipare?</h2>
                <p class="text-subtitle text-center">Puoi trovare i nostri prodotti nei punti vendita che trovi in questa sezione.</p>
                <form action="{{ route('stores.index') }}" method="GET" class="form-punti-vendita" id="store-search-form">
                    <select name="province" id="province-select">
                        <option value="">Seleziona la provincia</option>
                        @foreach ($provinces as $prov)
                            <option value="{{ $prov }}" {{ request('province') === $prov ? 'selected' : '' }}>{{ $prov }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="city" placeholder="Città" value="{{ request('city') }}">
                    <button type="submit" class="btn-mokador">Cerca</button>
                </form>
            </div>
        </div>
    </section>

    @if ($stores->count() > 0)
        <section>
            <div class="container">
                <ul class="punti-vendita-results">
                    @foreach ($stores as $store)
                        <li>
                            <img src="{{ asset('img/location-icon.svg') }}" alt="Location Icon">
                            <p>{{ $store->name }} - {{ $store->address }} {{ $store->city }} ({{ $store->province }})</p>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @elseif (request()->has('province') || request()->has('city'))
        <section>
            <div class="container">
                <p class="text-subtitle text-center py-4">Nessun punto vendita trovato per i criteri selezionati.</p>
            </div>
        </section>
    @endif
@endsection
