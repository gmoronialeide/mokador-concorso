# FASE 7 - Testing, Sicurezza e Deploy

**Priorità:** ALTA
**Dipendenze:** Fasi 1-6
**Stima effort:** Fase finale
**Stato:** 🔄 IN CORSO

---

## 7.1 Testing

### Test Algoritmo Instant Win — ✅ GIÀ COMPLETATI (Fase 3)
I seguenti test esistono già in `tests/Feature/InstantWinServiceTest.php` e `tests/Feature/GenerateWinningSlotsTest.php`:
- [x] Test generazione 104 slot con distribuzione corretta per giorno/premio
- [x] Test vincita: slot disponibile viene assegnato
- [x] Test non vincita: nessuno slot disponibile
- [x] Test vincolo punto vendita: max 1 premio/settimana/PV
- [x] Test concorrenza: lock pessimistico previene doppia assegnazione
- [x] Test regola ore 12: dopo le 12 se nessun premio assegnato, il primo giocatore valido vince
- [x] Test regola ore 12 + vincolo PV: dopo le 12, PV che ha già vinto nella settimana non vince

### Test Autenticazione — ✅ COMPLETATI (`tests/Feature/AuthenticationTest.php`)
- [x] Registrazione con dati validi → redirect + utente creato
- [x] Registrazione con email duplicata → errore validazione
- [x] Registrazione minorenni (< 18 anni) → errore validazione
- [x] Login valido → redirect a /gioca-ora
- [x] Login errato → errore
- [x] Accesso GET /gioca-ora senza login → redirect /login
- [x] Verifica email: utente non verificato non può giocare (redirect a verification.notice)
- [x] Login utente non verificato → redirect a verification.notice
- [x] Logout → redirect a home
- [x] Utente autenticato non può accedere a pagina login (guest middleware)
- [x] Password reset: pagina richiesta accessibile
- [x] Password reset: invio link a email valida
- [x] Password reset: email inesistente → errore
- [x] Password reset: form con token accessibile
- [x] Password reset: reset con token valido → password aggiornata
- [x] Password reset: reset con token invalido → errore
- [x] Turnstile: login con token invalido → errore validazione
- [x] Turnstile: registrazione con token invalido → errore validazione

### Test Giocata — ✅ COMPLETATI (`tests/Feature/GameControllerTest.php`)
- [x] Upload scontrino JPG valido → giocata creata
- [x] Upload scontrino PNG valido → giocata creata
- [x] Upload file > 6MB → errore validazione
- [x] Upload file non immagine → errore validazione
- [x] Codice punto vendita inesistente → errore validazione
- [x] Punto vendita disattivato → errore validazione
- [x] Seconda giocata nello stesso giorno → errore (hasPlayedToday)
- [x] Giocata fuori periodo concorso (prima inizio) → errore
- [x] Giocata fuori periodo concorso (dopo fine) → errore
- [x] Vincita → redirect /loading con result=won
- [x] Non vincita → redirect /loading con result=lost
- [x] Pagina loading renderizza
- [x] Pagina non-hai-vinto renderizza
- [x] Pagina hai-vinto senza premio in session → mostra lost

### Test Backoffice — ✅ COMPLETATI (`tests/Feature/BackofficeTest.php`)
- [x] Admin login con guard `admin` → accesso /admin
- [x] Credenziali `web` (User) non accedono a /admin
- [x] Utente non autenticato non accede a /admin
- [x] StoreResource: lista renderizza
- [x] StoreResource: creazione punto vendita
- [x] StoreResource: modifica punto vendita
- [x] PlayResource: ban giocata vincente → slot viene liberato (`is_assigned = false`)
- [x] PlayResource: sban giocata → premio NON riassegnato
- [x] UserResource: ban utente con motivazione
- [x] UserResource: sban utente
- [x] PlayResource: pagina view renderizza (con scontrino)
- [x] UserResource: pagina view renderizza
- [x] WinningSlotResource: lista renderizza
- [x] Utente bannato può accedere alla pagina gioco (mostra stato)

### Test Estrazione Finale — ✅ COMPLETATI (`tests/Feature/FinalDrawServiceTest.php` + `tests/Feature/FinalDrawPageTest.php`)
- [x] Test utenti eleggibili: esclude bannati e utenti con solo giocate bannate
- [x] Test utenti eleggibili: include vincitori instant win
- [x] Test utenti eleggibili: esclude utenti già estratti
- [x] Test conteggio giocate: conta solo giocate non bannate
- [x] Test peso: utente con più giocate ha più probabilità (test statistico 50 iterazioni)
- [x] Test drawWinners(): estrae esattamente 3 vincitori
- [x] Test drawWinners(): assegna ai premi corretti per posizione
- [x] Test drawWinners(): aggiorna drawn_at e drawn_by su FinalPrize
- [x] Test drawWinners(): nessun utente duplicato
- [x] Test drawWinners(): fallisce se già estratti
- [x] Test drawWinners(): fallisce con utenti insufficienti
- [x] Test drawSubstitutes(): fallisce se vincitori non ancora estratti
- [x] Test drawSubstitutes(): estrae 9 sostituti (3 per premio)
- [x] Test drawSubstitutes(): nessun sostituto è anche vincitore
- [x] Test drawSubstitutes(): nessun utente duplicato tra sostituti
- [x] Test drawSubstitutes(): fallisce se già estratti
- [x] Test reset sostituti: rimuove solo sostituti
- [x] Test reset tutto: rimuove tutto e resetta premi
- [x] Test total_plays registrato per audit
- [x] Test pagina Filament: accessibile solo ad admin (13 test)

---

## 7.2 Sicurezza

### Già implementato
- [x] **CSRF**: attivo su tutti i form (default Laravel)
- [x] **XSS**: escape output con `{{ }}` Blade (default)
- [x] **SQL Injection**: uso esclusivo di Eloquent/query builder parametrizzato
- [x] **Upload sicuro**: validazione MIME type reale, salvataggio in `storage/app/private/receipts/`
- [x] **Rate limiting HTTP**: `auth` 10/min per IP, `play` 5/min per utente autenticato (bootstrap/app.php)
- [x] **1 giocata/giorno/utente**: logica applicativa in `User::hasPlayedToday()`
- [x] **Password**: bcrypt (default Laravel)
- [x] **Admin separato**: guard `admin` con model `Admin`, impossibile accedere a /admin con credenziali User
- [x] **Turnstile**: protezione anti-bot su login e registrazione (Cloudflare Turnstile)
- [x] **Email verification**: utenti devono verificare email prima di giocare

### Verificato
- [x] **Session regenerate on login**: `$request->session()->regenerate()` in `AuthController::login()`
- [x] **Rinomina file upload**: Laravel `store()` genera automaticamente nome hash univoco (non nome originale)

### Da verificare in produzione
- [ ] **Session**: HttpOnly cookies (default Laravel), Secure flag (`SESSION_SECURE_COOKIE=true` in .env)
- [ ] **Headers sicurezza**: X-Content-Type-Options, X-Frame-Options, CSP base — configurare in Nginx/Apache
- [ ] **Upload**: confermare che non c'è esecuzione PHP nella cartella storage (config server)
- [ ] **Ambiente produzione**: `APP_DEBUG=false`, `APP_ENV=production`

---

## 7.3 Configurazione Server / Deploy

### Requisiti server
- [ ] PHP 8.3 con estensioni: mbstring, openssl, pdo_mysql, tokenizer, xml, ctype, json, bcmath, fileinfo, gd
- [ ] MySQL 5.7.44+ (o MySQL 8.0 — database-condiviso già in uso)
- [ ] Composer 2.x
- [ ] Node.js + npm (per build assets Vite)
- [ ] Accesso SSH (per artisan commands)
- [ ] Cron job (per scheduler Laravel)
- [ ] SMTP configurato (per email vincita e verifica email)
- [ ] Cloudflare Turnstile: chiavi site/secret configurate in .env

### Docker (sviluppo)
Il progetto include un `Dockerfile` con Nginx + PHP-FPM + Supervisord. Per il deploy:
- [ ] Decidere se usare Docker anche in produzione o deploy tradizionale
- [ ] Se Docker: verificare `docker/nginx.conf`, `docker/php.ini`, `docker/supervisord.conf`
- [ ] Se tradizionale: procedura manuale sotto

### Procedura deploy (tradizionale)
- [ ] Upload codice (git pull o rsync, esclusi: `.env`, `storage/app`, `node_modules`, `vendor`)
- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] `npm ci && npm run build`
- [ ] `php artisan migrate --force`
- [ ] `php artisan config:cache`
- [ ] `php artisan route:cache`
- [ ] `php artisan view:cache`
- [ ] `php artisan storage:link`
- [ ] Verificare permessi cartella `storage/` e `bootstrap/cache/` (775)
- [ ] Configurare virtual host con document root su `public/`

### Cron job
- [ ] Aggiungere al crontab: `* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1`
- [ ] Nello scheduler Laravel (se necessario):
  - `queue:work --stop-when-empty` → every minute (se queue driver = database)

> **Nota:** Il command `concorso:recover-slots` è stato eliminato (Fase 3). Gli slot non vinti restano non assegnati — non serve recovery notturno.

### Variabili .env di produzione
- [ ] `APP_ENV=production`, `APP_DEBUG=false`
- [ ] `APP_URL=https://invacanza.mokador.it`
- [ ] `DB_*` — credenziali MySQL produzione
- [ ] `MAIL_*` — SMTP produzione (mittente: concorso@mokador.it)
- [ ] `CONCORSO_START_DATE=2026-04-20`, `CONCORSO_END_DATE=2026-05-17`
- [ ] `ADMIN_EMAIL`, `ADMIN_PASSWORD` — credenziali admin iniziale
- [ ] `TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY` — chiavi Cloudflare Turnstile produzione
- [ ] `SESSION_SECURE_COOKIE=true` (HTTPS)

---

## 7.4 Pre-lancio (prima del 20 aprile 2026)

- [ ] Eseguire `php artisan migrate --force` per creare tutte le tabelle
- [ ] Eseguire `php artisan db:seed --class=PrizeSeeder` per inserire i 5 premi (A-E)
- [ ] Eseguire `php artisan db:seed --class=AdminSeeder` per creare admin
- [ ] Eseguire `php artisan db:seed --class=FinalPrizeSeeder` per i 3 premi finali (Fase 6)
- [ ] Eseguire `php artisan concorso:generate-slots` per generare i 104 slot vincenti
- [ ] Caricare punti vendita (inserimento manuale via Filament o import CSV se implementato)
- [ ] Caricare PDF regolamento in `public/docs/regolamento.pdf`
- [ ] Caricare PDF privacy in `public/docs/privacy.pdf`
- [ ] Test completo end-to-end in ambiente staging
- [ ] Verificare invio email (verifica email + notifica vincita) da SMTP di produzione
- [ ] Verificare Turnstile funzionante con chiavi di produzione
- [ ] Backup database automatico configurato
- [ ] Verificare che la dashboard Filament mostri dati corretti

---

## 7.5 Post-lancio e monitoraggio

- [ ] Monitorare dashboard Filament per statistiche giornaliere (widget premi, giocate, vincite)
- [ ] Verificare che i premi vengano assegnati quotidianamente (controllare WinningSlotResource)
- [ ] Controllare log errori (`storage/logs/laravel.log` e canale `concorso`)
- [ ] Dopo il 17 maggio 2026: le giocate si bloccano automaticamente (check `CONCORSO_END_DATE`)
- [ ] Entro il 12 giugno 2026: effettuare estrazione finale dalla pagina Filament (Fase 6)
- [ ] Esportare verbale notarile dall'interfaccia Filament

---

## Note

- L'estrazione finale è ora gestita nel sistema (Fase 6) con pagina Filament dedicata, estrazione pesata, e export verbale
- Il recovery slot notturno NON esiste: slot non vinti restano non assegnati (decisione Fase 3)
- Il progetto usa Docker per lo sviluppo — valutare se usarlo anche in produzione
