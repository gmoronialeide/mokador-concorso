@extends('layouts.app')

@section('content')
    <section>
        <div class="teaser">
            <img class="teaser-home" src="{{ asset('img/teaser-img.jpg') }}" alt="Teaser Home">
            <div class="teaser-home-overlay"></div>
            <div class="teaser-home-content">
                <img class="d-none d-md-flex teaser-home-persone" src="{{ asset('img/teaser-persone.png') }}" alt="Persone">
                <img class="teaser-home-logo" src="{{ asset('img/mokador-concorso-logo.svg') }}" alt="Mokador Concorso Logo">
            </div>
        </div>
    </section>

    <section>
        <div class="container">
            <div class="home-rules">
                <h2 class="section-title-dark">Come partecipare?</h2>
                <ul>
                    <li>
                        <img src="{{ asset('img/home-rules-point.svg') }}" alt="">
                        <p>Vai in un <strong>punto vendita aderente</strong></p>
                    </li>
                    <li>
                        <img src="{{ asset('img/home-rules-point.svg') }}" alt="">
                        <p>Acquista almeno un prodotto <strong>Mokador, Sacao o Caffè Gualtieri</strong>*</p>
                    </li>
                    <li>
                        <img src="{{ asset('img/home-rules-point.svg') }}" alt="">
                        <p>Accedi al sito e <a href="{{ route('register') }}">registrati</a></p>
                    </li>
                    <li>
                        <img src="{{ asset('img/home-rules-point.svg') }}" alt="">
                        <p>Carica lo scontrino e <strong>scopri subito se hai vinto!</strong></p>
                    </li>
                    <li>
                        <img src="{{ asset('img/home-rules-point.svg') }}" alt="">
                        <p>Ricordati di conservare lo scontrino e aspetta l'<strong>estrazione finale</strong></p>
                    </li>
                </ul>
                <div class="w-100 d-flex justify-content-center align-items-center flex-column flex-md-row gap-3">
                    <a href="{{ route('stores.index') }}" class="btn-mokador">Dove?</a>
                    <a href="{{ route('game.show') }}" class="btn-mokador">Gioca subito</a>
                </div>
                <p class="small-caption text-center w-100">*Concorso non valido per gli acquisti online</p>
            </div>
        </div>
    </section>

    <section class="section-dark">
        <div class="container position-relative">
            <div class="date-disclaimer">
                <img src="{{ asset('img/date-disclaimer-sx.svg') }}" alt="">
                <p>Dal 20 aprile al 17 maggio</p>
                <img src="{{ asset('img/date-disclaimer-dx.svg') }}" alt="">
            </div>
            <div class="d-flex justify-content-center align-items-center flex-column py-5 gap-4">
                <h2 class="section-title-light pt-5">Premi settimanali</h2>
                <p class="text-light text-center text-subtitle">Ogni giorno <strong>tenta la fortuna</strong>:<br>scopri subito se hai vinto!</p>
                <div class="grid-five-card">
                    <div class="product-card">
                        <div class="card-content">
                            <h4 class="card-title">Caffè macinato Mokador Espresso Oro</h4>
                            <p class="card-subtitle">7 a settimana</p>
                        </div>
                        <img src="{{ asset('img/macinato-oro.png') }}" alt="">
                    </div>
                    <div class="product-card">
                        <div class="card-content">
                            <h4 class="card-title">Caffè macinato Mokador Latta 100% Arabica</h4>
                            <p class="card-subtitle">7 a settimana</p>
                        </div>
                        <img src="{{ asset('img/latta-arabica.png') }}" alt="">
                    </div>
                    <div class="product-card">
                        <div class="card-content">
                            <h4 class="card-title">Bicchierini a cuore Mokador</h4>
                            <p class="card-subtitle">5 a settimana</p>
                        </div>
                        <img src="{{ asset('img/bicchierini.png') }}" alt="">
                    </div>
                    <div class="product-card">
                        <div class="card-content">
                            <h4 class="card-title">T-shirt Mokador in 4 colori diversi</h4>
                            <p class="card-subtitle">4 a settimana</p>
                        </div>
                        <img src="{{ asset('img/maglietta.png') }}" alt="">
                    </div>
                    <div class="product-card">
                        <div class="card-content">
                            <h4 class="card-title">Grembiuli a pettorina</h4>
                            <p class="card-subtitle">3 a settimana</p>
                        </div>
                        <img src="{{ asset('img/grembiuli.png') }}" alt="">
                    </div>
                </div>
                <div class="recipe-warning">
                    <h3>Mi raccomando conserva lo scontrino</h3>
                </div>
            </div>
        </div>
    </section>

    <section class="section-cream background-prize-wrapper">
        <div class="container z-2 position-relative">
            <div class="d-flex justify-content-center align-items-center flex-column py-5 gap-4">
                <h2 class="section-title-dark text-center">Estrazione finale</h2>
                <p class="text-subtitle text-center">Non hai vinto i premi settimanali?<br>
                    Conservando lo scontrino parteciperai automaticamente all'<strong>estrazione finale*!</strong></p>
                <p class="small-caption text-center w-100">*L'estrazione verrà effettuata entro il 12 giugno</p>
            </div>
            <div class="prize-container">
                <div class="image-container order-2 order-lg-1">
                    <img class="hotel-img-bg" src="{{ asset('img/hotel-esterno.jpg') }}" alt="">
                    <img class="hotel-img-sm" src="{{ asset('img/hotel-camera.jpg') }}" alt="">
                </div>
                <div class="d-flex flex-column gap-3 align-items-center justify-content-center order-1 order-lg-2">
                    <p class="prize-numeration">1° PREMIO</p>
                    <h3 class="section-subtitle">soggiorno per 2 persone<br>al Family Hotel Gran Baita</h3>
                    <p class="text-subtitle text-center">Il primo premio è <strong>una settimana per 2 persone</strong> presso il <a href="https://www.familyhotelgranbaita.it/" target="_blank" rel="noopener">Family Hotel Gran Baita</a>, immerso nella splendida Val di Fassa, esplora e rilassati tra i paesaggi suggestivi delle Dolomiti.</p>
                    <p class="text-subtitle text-center">Il soggiorno è valido esclusivamente dall'11 al 18 luglio 2026. Il vincitore dovrà confermare la propria presenza direttamente all'hotel entro il 30 giugno 2026.</p>
                    <p class="text-subtitle text-center"><strong>Non sono incluse le spese di viaggio e i servizi non espressamente ricompresi</strong></p>
                </div>
            </div>
        </div>
        <img class="background-prize-sx z-1" src="{{ asset('img/montagna-angolo.png') }}" alt="">
    </section>

    <section class="background-prize-wrapper">
        <div class="container z-2 position-relative">
            <div class="prize-container gap-4">
                <div class="d-flex flex-column gap-3 align-items-center justify-content-center">
                    <p class="prize-numeration">2° PREMIO</p>
                    <h3 class="section-subtitle">Macchina caffè<br>Mokador Diva E1</h3>
                    <p class="text-subtitle text-center">Il secondo premio è la <strong>macchina caffè Diva E1 di Mokador, compatta ed elegante</strong>, perfetta per un espresso cremoso come al bar.</p>
                    <p class="text-subtitle text-center">In regalo anche <strong>100 capsule di caffè</strong> per iniziare subito a gustarla.</p>
                </div>
                <img class="prize-img-cream" src="{{ asset('img/macchina-caffe.png') }}" alt="">
            </div>
        </div>
        <img class="background-prize-dx z-1" src="{{ asset('img/caffe-angolo.jpg') }}" alt="">
    </section>

    <section class="section-cream background-prize-wrapper">
        <div class="container z-2 position-relative">
            <div class="prize-container gap-4">
                <img class="prize-img-white order-2 order-lg-1" src="{{ asset('img/montalatte.png') }}" alt="">
                <div class="d-flex flex-column gap-3 align-items-center justify-content-center order-1 order-lg-2">
                    <p class="prize-numeration">3° PREMIO</p>
                    <h3 class="section-subtitle">Montalatte</h3>
                    <p class="text-subtitle text-center">Il terzo premio è un pratico <strong>montalatte</strong>, perfetto per preparare a casa cappuccini cremosi e soffici schiume di latte come al bar.</p>
                </div>
            </div>
        </div>
        <img class="background-prize-sx z-1" src="{{ asset('img/latte-angolo.png') }}" alt="">
    </section>

    <section>
        <div class="container py-5">
            <div class="d-flex flex-column gap-4 align-items-center justify-content-center py-5">
                <h2 class="section-title-dark text-center">Gioca ora</h2>
                <p class="text-subtitle text-center">Gioca subito, hai una <strong>opportunità giornaliera</strong> per vincere <strong>fantastici premi</strong><br>e alla fine parteciperai all'<strong>estrazione finale</strong>!</p>
                <a href="{{ auth()->check() ? route('game.show') : route('register') }}" class="btn-mokador">Registrati e Vinci</a>
            </div>
        </div>
    </section>
@endsection
