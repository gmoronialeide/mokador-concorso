# FASE 4 - Frontend: Conversione HTML → Blade + Controller

**Priorità:** ALTA
**Dipendenze:** Fase 1, Fase 2
**Stima effort:** Fase con più volume di lavoro, ma bassa complessità
**Stato:** ✅ COMPLETATA

---

## 4.1 Layout Base

- [x] Creare `resources/views/layouts/app.blade.php` con:
  - Header fisso con navigazione (estratto dalle pagine statiche)
  - Footer comune
  - Inclusione CSS (`style.css` + Bootstrap 5 CDN + Adobe Typekit)
  - Inclusione JS (`main.js` + Bootstrap JS CDN)
  - Meta tags SEO base
  - `@yield('content')` per contenuto pagina
  - `@yield('title')` per titolo pagina
  - Flash messages (successo, errore)
  - CSRF meta tag per eventuali chiamate AJAX

## 4.2 Rotte (`routes/web.php`)

- [x] `GET /` → HomeController@index (homepage)
- [x] `GET /login` → AuthController@showLogin
- [x] `POST /login` → AuthController@login
- [x] `POST /logout` → AuthController@logout
- [x] `GET /registrati` → AuthController@showRegister
- [x] `POST /registrati` → AuthController@register
- [x] `GET /gioca-ora` → GameController@show (auth required)
- [x] `POST /gioca-ora` → GameController@play (auth required)
- [x] `GET /hai-vinto` → GameController@won (auth required, session flash)
- [x] `GET /non-hai-vinto` → GameController@lost (auth required, session flash)
- [x] `GET /loading` → GameController@loading (auth required)
- [x] `GET /punti-vendita` → StoreController@index
- [x] `GET /api/stores` → StoreController@search (JSON per ricerca AJAX)
- [x] `GET /regolamento` → redirect a PDF in `public/docs/regolamento.pdf`
- [x] `GET /privacy` → redirect a PDF in `public/docs/privacy.pdf`

## 4.3 Controller e logica

### AuthController
- [x] `showLogin()` - render form login
- [x] `login()` - validazione (LoginRequest), autenticazione, redirect a /gioca-ora
- [x] `logout()` - logout, redirect a /
- [x] `showRegister()` - render form registrazione
- [x] `register()` - validazione (RegisterRequest), creazione utente, auto-login, redirect

### GameController
- [x] `show()` - render form gioca (con check: ha già giocato oggi?)
- [x] `play()` - validazione (PlayGameRequest), upload scontrino, creazione Play, chiamata InstantWinService, redirect
- [x] `loading()` - pagina loading (animazione Lottie), redirect JS dopo 3 secondi
- [x] `won()` - pagina vincita (legge premio da session flash)
- [x] `lost()` - pagina non vincita

### StoreController
- [x] `index()` - pagina punti vendita con form ricerca
- [x] `search(Request)` - endpoint JSON per ricerca AJAX (filtro provincia + città)

### HomeController
- [x] `index()` - homepage

---

## 4.4 Conversione pagine Blade

- [x] `home.blade.php` ← `index.html`
- [x] `auth/login.blade.php` ← `login.html`
- [x] `auth/register.blade.php` ← `register.html`
- [x] `game/play.blade.php` ← `gioca-ora.html`
- [x] `game/loading.blade.php` ← `loading.html`
- [x] `game/won.blade.php` ← `hai-vinto.html`
- [x] `game/lost.blade.php` ← `non-hai-vinto.html`
- [x] `stores/index.blade.php` ← `punti-vendita.html`
- [x] `errors/404.blade.php` ← `404.html`
- [x] `partials/teaser.blade.php` — teaser secondario riutilizzabile

---

## 4.5 Upload Scontrini

- [x] Salvare in `storage/app/private/receipts/` (disk `receipts`)
- [x] Validare MIME type reale: `image/jpeg`, `image/png`, max 6MB
- [ ] Rotta protetta per visualizzazione scontrino da admin: `GET /admin/receipt/{play}` (Fase 5)

---

## 4.6 Email Vincita

- [x] Creare Mailable `WinNotification` (implements ShouldQueue)
- [x] Template Blade `emails/win-notification.blade.php`
- [x] Dati: nome vincitore, premio vinto, istruzioni convalida
- [x] Dispatch tramite Job in queue (non bloccare la risposta)
- [x] Indirizzo mittente: concorso@mokador.it

---

## 4.7 Form Requests

- [x] `LoginRequest` — email required, password required
- [x] `RegisterRequest` — validazione completa con maggiore età, unique email, password confirmed
- [x] `PlayGameRequest` — store_code valido e attivo, receipt file mimes/max

---

## Note

- Il frontend è già completo come design: il lavoro è solo di integrazione con Blade
- La pagina loading usa Lottie (dotlottie-wc): mantenuto CDN esterno
- La ricerca punti vendita funziona sia con submit form che con endpoint JSON
- Regolamento e privacy → redirect a PDF in `public/docs/` (file da caricare)
- Nav header mostra "Esci" (con form POST logout) quando l'utente è autenticato
