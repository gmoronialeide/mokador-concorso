<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @production
    <meta name="robots" content="index, follow">
    @else
    <meta name="robots" content="noindex, nofollow">
    @endproduction
    <title>@yield('title', 'Mokador ti porta in vacanza')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="https://use.typekit.net/zzx8hhh.css">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="icon" href="{{ asset('img/loco-icon.svg') }}" type="image/svg+xml">
    @stack('head')
    @production
    <!-- Google Tag Manager by gtm4wp.com -->
    <script data-cfasync="false" data-pagespeed-no-defer>
        var dataLayer_content = {"pageType":"frontpage","pagePostType2":"single-page","pagePostAuthor":"mokador"};
        dataLayer.push(dataLayer_content);
    </script>
    <script data-cfasync="false" data-pagespeed-no-defer>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    '//www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-PRL8WFGW');</script>
    <!-- End Google Tag Manager -->
    @endproduction
</head>

<body>
    @production
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-PRL8WFGW" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
    @endproduction

    <header>
        <div class="container">
            <div class="header-content">
                <a href="{{ route('home') }}">
                    <img src="{{ asset('img/logo-mokador-header.png') }}" alt="Logo">
                </a>
                <nav id="main-nav">
                    <ul>
                        <li><a href="{{ route('home') }}" @class(['active' => request()->routeIs('home')])>Home</a></li>
                        <li><a href="{{ route('stores.index') }}" @class(['active' => request()->routeIs('stores.*')])>Punti Vendita</a></li>
                        <li><a href="{{ route('game.show') }}" @class(['active' => request()->routeIs('game.*')])>Gioca Ora</a></li>
                        @guest
                            <li><a href="{{ route('login') }}" @class(['active' => request()->routeIs('login') || request()->routeIs('register')])>Accedi</a></li>
                        @else
                            <li><a href="{{ route('logout') }}" onclick="event.preventDefault();this.closest('li').querySelector('form').submit();">Esci</a>
                                <form method="POST" action="{{ route('logout') }}" style="display:none">@csrf</form>
                            </li>
                        @endguest
                    </ul>
                </nav>
                <button class="burger-menu" id="burger-toggle" aria-label="Menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
    </header>

    <main>
        @if (session('success'))
            <div class="container">
                <div class="alert alert-success mt-3">{{ session('success') }}</div>
            </div>
        @endif

        @if (session('error'))
            <div class="container">
                <div class="alert alert-danger mt-3">{{ session('error') }}</div>
            </div>
        @endif

        @yield('content')
    </main>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-sx">
                    <p>Per assistenza: <a href="mailto:concorso@mokador.it">concorso@mokador.it</a></p>
                    <div class="footer-items">
                        <p>Copyright &copy; 2026 Mokador Srl</p>
                        <span class="footer-separator">|</span>
                        <p>Tutti i diritti riservati</p>
                        <span class="footer-separator">|</span>
                        <p>P.IVA 02401670399</p>
                    </div>
                </div>
                <div class="footer-dx">
                    <div class="footer-items">
                        <p><a href="{{ route('regolamento') }}" target="_blank" rel="noopener">Regolamento</a></p>
                        <span class="footer-separator">|</span>
                        <p><a href="{{ route('privacy') }}" target="_blank" rel="noopener">Privacy policy</a></p>
                        <span class="footer-separator">|</span>
                        <p><a href="{{ route('cookie.policy') }}" target="_blank" rel="noopener">Cookie policy</a></p>
                        <span class="footer-separator">|</span>
                        <p><a href="https://www.aleidewebagency.com/" rel="nofollow" target="_blank" title="Realizzazione Concorso a Premi online">Concorso sviluppato da Aleide</a></p>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    @if ($errors->any())
        <div id="error-modal-backdrop" onclick="if(event.target===this)this.remove()" style="position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:10000;display:flex;align-items:center;justify-content:center;cursor:pointer">
            <div style="background:#fff;border-radius:12px;max-width:500px;width:90%;max-height:80vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.3);cursor:default">
                <div style="background:#2B1D18;padding:16px 20px;border-radius:12px 12px 0 0;display:flex;justify-content:space-between;align-items:center">
                    <h3 style="color:#DFD6C1;margin:0;font-size:1.1rem">Errori nel modulo</h3>
                    <button onclick="document.getElementById('error-modal-backdrop').remove()" style="background:none;border:none;color:#DFD6C1;font-size:1.5rem;cursor:pointer;line-height:1">&times;</button>
                </div>
                <ul style="list-style:none;padding:20px;margin:0">
                    @foreach ($errors->all() as $error)
                        <li style="padding:8px 0;border-bottom:1px solid #f0ebe0;color:#614637;font-size:.95rem">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <script src="{{ asset('js/main.js') }}"></script>
    <script>
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function() {
            var btn = form.querySelector('button[type="submit"], button:not([type])');
            if (btn) {
                btn.disabled = true;
                btn.style.opacity = '0.6';
                btn.style.cursor = 'not-allowed';
            }
        });
    });
    </script>
    @stack('scripts')

</body>

</html>
