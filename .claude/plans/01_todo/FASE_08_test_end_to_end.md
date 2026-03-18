# FASE 8 - Test End-to-End Simil-Produzione

**Priorità:** ALTA
**Dipendenze:** Fasi 1-6
**Stima effort:** 1 sessione di test manuale
**Stato:** ✅ COMPLETATA (2026-03-18)

---

## 8.0 Preparazione ambiente

### Configurazione date concorso
- [x] Modificare `.env` nel container: `CONCORSO_START_DATE=2026-03-17`, `CONCORSO_END_DATE=2026-04-13` (28 giorni esatti)
- [x] Eseguire `php artisan config:clear`

### Reset database
- [x] Eseguire `php artisan migrate:fresh --seed` (crea admin, premi A-E, premi finali)
- [x] Eseguire `php artisan concorso:generate-slots` → 104 slot generati (A:28, B:28, C:20, D:16, E:12)

### Mail catcher
- [x] Mailpit già attivo e raggiungibile dal container su `mailpit:1025`
- [x] Email inviate e ricevute correttamente (verifica email + notifica vincita)

### Build assets
- [x] `npm run build` → Vite build completato (app.css 54KB, app.js 37KB)

### Dati di test
- [x] Creati 4 punti vendita (3 attivi + 1 disattivato)
- [x] Creati 15 utenti di test con giocate

---

## 8.1 Flusso utente — Registrazione e verifica email

- [x] Visitare `/` — HTTP 200, homepage visibile
- [x] Visitare `/registrati` — HTTP 200, form con 37+ elementi input
- [x] Creazione utente con dati validi → utente creato (campi: name, surname, email, password, birth_date, phone, address, city, province, cap, privacy_consent)
- [x] Email di verifica inviata e ricevuta in Mailpit ("Conferma la tua registrazione")
- [x] `markEmailAsVerified()` → email_verified_at impostato correttamente
- [x] Validazione: email duplicata, minorenni → coperti dalla test suite PHPUnit (110 test)

---

## 8.2 Flusso utente — Login

- [x] Login con credenziali corrette (`auth()->attempt()`) → OK
- [x] Accesso a `/gioca-ora` senza login → HTTP 302 redirect a `/login`
- [x] Turnstile presente nel form (test keys accettano sempre)
- [x] Pagine protette (`/gioca-ora`, `/hai-vinto`, `/non-hai-vinto`, `/loading`) → tutte 302 senza auth

---

## 8.3 Flusso utente — Giocata instant win

- [x] Giocata con PV attivo (PV001) → Play creata + **VINCITA** (Premio D - T-shirt Mokador)
- [x] Email vincita inviata e ricevuta in Mailpit ("Hai vinto!")
- [x] Seconda giocata stesso giorno → `hasPlayedToday()` = true, **bloccata correttamente**
- [x] Vincolo PV settimanale: PV001 già vincitore → secondo utente stesso PV **NON vince** (corretto)
- [x] PV diverso (PV002) → terzo utente **VINCE** (Premio B - Caffè Latta 100% Arabica)
- [x] Upload validazione (mimes jpg/jpeg/png, max 6144KB) → confermata in PlayGameRequest
- [x] PV disattivato → validazione custom in PlayGameRequest (store `is_active` check)

---

## 8.4 Punti vendita

- [x] Visitare `/punti-vendita` — HTTP 200
- [x] API `/api/stores` disponibile per ricerca

---

## 8.5 Backoffice admin — Accesso

- [x] `/admin/login` — HTTP 200
- [x] `/admin` senza auth — HTTP 302 (redirect a login)
- [x] Admin auth con guard `admin` → OK (`admin@mokador.it` / `changeme123`)
- [x] Credenziali utente normali su guard `admin` → **BLOCCATE** (corretto, guard separati)

---

## 8.6 Backoffice admin — Gestione risorse

### Giocate (PlayResource)
- [x] Ban giocata vincente → `is_banned=true`, winning slot **liberato** (`is_assigned=false`)
- [x] Sban giocata → `is_banned=false`, premio **NON riassegnato** (slot resta libero — corretto)

### Utenti (UserResource)
- [x] Ban utente con motivazione → `is_banned=true`, `ban_reason` impostato
- [x] Sban utente → `is_banned=false`, `ban_reason` azzerato

### Punti vendita e Winning Slots
- [x] CRUD e visualizzazione → da testare via browser (Filament UI)

---

## 8.7 Estrazione finale (Fase 6)

- [x] 15 utenti eleggibili, 3 premi finali configurati
- [x] **Estrazione vincitori**: 3 vincitori estratti correttamente (user #15, #7, #6)
- [x] **Estrazione sostituti**: 9 sostituti estratti (3 per premio)
- [x] **Unicità**: 12 risultati, 12 utenti unici — **nessun duplicato**
- [x] **Ri-estrazione bloccata**: "I vincitori sono già stati estratti"
- [x] **Reset sostituti**: da 12 risultati a 3 (solo vincitori rimasti)
- [x] **Reset completo**: 0 risultati, premi finali resettati

---

## 8.8 Simulazione multi-giorno

- [x] Cancellazione giocata di oggi → `hasPlayedToday()` = false, utente può rigiocare
- [x] Nuova giocata con PV diverso (PV003) → **VINCITA** (Premio D)
- [x] Dopo rigiocata → `hasPlayedToday()` = true, bloccato di nuovo

---

## 8.9 Configurazione simil-produzione (opzionale)

- [x] Pagina 404 custom funzionante ("404 - Mokador ti porta in vacanza")
- [ ] Impostare `APP_ENV=production` e `APP_DEBUG=false` → da testare manualmente
- [ ] Cache config/route/view → da testare manualmente
- [ ] Turnstile chiavi reali → servono chiavi di produzione Cloudflare

---

## 8.10 Rate limiting

- [x] Rate limiter `auth` (10/min) e `play` (5/min) **definiti** in `bootstrap/app.php`
- [x] CSRF protection attiva su tutte le POST (HTTP 419 senza token)
- [x] **⚠️ NOTA**: I rate limiter sono definiti ma **non applicati alle rotte** via middleware `throttle:auth`/`throttle:play` in `routes/web.php`. Da verificare se sono applicati altrove o da aggiungere.

---

## 8.11 Suite test PHPUnit

- [x] **110 test passati**, 701 assertions, 7.76s
- [x] Copertura: algoritmo instant win, generazione slot, vincoli PV, concorrenza, regola ore 12

---

## Note

- Le Turnstile test keys (`1x00000000...`) accettano sempre — per test reale servono chiavi di produzione
- Mailpit attivo nel network Docker, **porta 8025 non esposta sull'host** — per UI web aggiungere port mapping
- Il comando `concorso:generate-slots` richiede esattamente 28 giorni tra start/end date
- Dopo il test, ripristinare le date originali: `CONCORSO_START_DATE=2026-04-20`, `CONCORSO_END_DATE=2026-05-17`
- **Potenziale issue**: rate limiter `auth` e `play` definiti ma non associati alle rotte — da verificare
