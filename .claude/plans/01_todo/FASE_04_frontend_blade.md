# FASE 4 - Frontend: Conversione HTML → Blade + Controller

**Priorità:** ALTA
**Dipendenze:** Fase 1, Fase 2
**Stima effort:** Fase con più volume di lavoro, ma bassa complessità

---

## 4.1 Layout Base

- [ ] Creare `resources/views/layouts/app.blade.php` con:
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

- [ ] `GET /` → HomeController@index (homepage)
- [ ] `GET /login` → AuthController@showLogin
- [ ] `POST /login` → AuthController@login
- [ ] `POST /logout` → AuthController@logout
- [ ] `GET /registrati` → AuthController@showRegister
- [ ] `POST /registrati` → AuthController@register
- [ ] `GET /gioca-ora` → GameController@show (auth required)
- [ ] `POST /gioca-ora` → GameController@play (auth required)
- [ ] `GET /hai-vinto` → GameController@won (auth required, session flash)
- [ ] `GET /non-hai-vinto` → GameController@lost (auth required, session flash)
- [ ] `GET /loading` → GameController@loading (auth required)
- [ ] `GET /punti-vendita` → StoreController@index
- [ ] `GET /api/stores` → StoreController@search (JSON per ricerca AJAX)
- [ ] `GET /regolamento` → PageController@regolamento
- [ ] `GET /privacy` → PageController@privacy

## 4.3 Controller e logica

### AuthController
- [ ] `showLogin()` - render form login
- [ ] `login()` - validazione, autenticazione, redirect a /gioca-ora
- [ ] `logout()` - logout, redirect a /
- [ ] `showRegister()` - render form registrazione
- [ ] `register()` - validazione completa, creazione utente, auto-login, redirect

**Validazione registrazione:**
```
name: required|string|max:100
surname: required|string|max:100
birth_date: required|date|before:-18 years (maggiorenni)
email: required|email|unique:users
phone: required|string|max:20
address: required|string|max:255
city: required|string|max:100
province: required|string|size:2
cap: required|string|size:5
password: required|string|min:8|confirmed
privacy_consent: required|accepted
marketing_consent: nullable|boolean
```

### GameController
- [ ] `show()` - render form gioca (con check: ha già giocato oggi?)
- [ ] `play()` - validazione, upload scontrino, creazione Play, chiamata InstantWinService, redirect
- [ ] `loading()` - pagina loading (animazione Lottie), redirect JS dopo N secondi
- [ ] `won()` - pagina vincita (legge premio da session flash)
- [ ] `lost()` - pagina non vincita

**Validazione giocata:**
```
store_code: required|string|exists:stores,code (punto vendita deve esistere e essere attivo)
receipt: required|file|mimes:jpg,jpeg,png|max:6144 (6MB)
```

**Flusso giocata:**
```
1. POST /gioca-ora con scontrino + codice punto vendita
2. Validazione input
3. Check: utente ha già giocato oggi? → errore
4. Check: codice punto vendita valido e attivo? → errore
5. Upload scontrino → storage/app/receipts/{userId}_{timestamp}.{ext}
6. Crea record Play
7. Chiama InstantWinService::attempt($play)
8. Redirect a /loading con flash (esito)
9. Loading mostra animazione, poi redirect a /hai-vinto o /non-hai-vinto
```

### StoreController
- [ ] `index()` - pagina punti vendita con form ricerca
- [ ] `search(Request)` - endpoint JSON per ricerca AJAX (filtro provincia + città)

### HomeController
- [ ] `index()` - homepage (eventualmente con check concorso attivo/non attivo per date)

---

## 4.4 Conversione pagine Blade

Per ogni pagina, partire dall'HTML statico esistente in `public/static/`:

- [ ] `home.blade.php` ← `index.html`
- [ ] `auth/login.blade.php` ← `login.html`
- [ ] `auth/register.blade.php` ← `register.html`
- [ ] `game/play.blade.php` ← `gioca-ora.html`
- [ ] `game/loading.blade.php` ← `loading.html`
- [ ] `game/won.blade.php` ← `hai-vinto.html`
- [ ] `game/lost.blade.php` ← `non-hai-vinto.html`
- [ ] `stores/index.blade.php` ← `punti-vendita.html`
- [ ] `errors/404.blade.php` ← `404.html`

**Per ogni pagina:**
- Estrarre header/footer nel layout
- Sostituire path relativi con `{{ asset('css/...') }}`
- Aggiungere `@csrf` ai form
- Aggiungere `action="{{ route('...') }}"` ai form
- Aggiungere `@error('campo')` per messaggi di validazione
- Aggiungere `old('campo')` per mantenere valori dopo errore
- Aggiungere condizionali Blade dove necessario (`@auth`, `@guest`, ecc.)

---

## 4.5 Upload Scontrini

- [ ] Salvare in `storage/app/private/receipts/` (non accessibile pubblicamente)
- [ ] Naming: `{userId}_{playId}_{timestamp}.{ext}`
- [ ] Validare MIME type reale (non solo estensione): `image/jpeg`, `image/png`
- [ ] Rotta protetta per visualizzazione scontrino da admin: `GET /admin/receipt/{play}`

---

## 4.6 Email Vincita

- [ ] Creare Mailable `WinNotification`
- [ ] Template Blade da `mail.html` esistente
- [ ] Dati: nome vincitore, premio vinto, istruzioni convalida
- [ ] Dispatch tramite Job in queue (non bloccare la risposta)
- [ ] Indirizzo mittente: concorso@mokador.it

---

## Note

- Il frontend è già completo come design: il lavoro è solo di integrazione con Blade
- La pagina loading usa Lottie (dotlottie-wc): mantenere il CDN esterno
- La ricerca punti vendita deve funzionare in AJAX per UX fluida
- Considerare middleware `concorso.active` che verifica date concorso (prima del 20/04 e dopo 17/05 mostra pagina "concorso non attivo")
