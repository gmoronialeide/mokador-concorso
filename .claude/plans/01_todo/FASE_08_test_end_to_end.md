# FASE 8 - Test End-to-End Simil-Produzione

**Priorità:** ALTA
**Dipendenze:** Fasi 1-6
**Stima effort:** 1 sessione di test manuale
**Stato:** ⬜ DA FARE

---

## 8.0 Preparazione ambiente

### Configurazione date concorso
- [ ] Modificare `.env` nel container: `CONCORSO_START_DATE` e `CONCORSO_END_DATE` per includere la data odierna
- [ ] Eseguire `php artisan config:clear`

### Reset database
- [ ] Eseguire `php artisan migrate:fresh --seed` (crea admin, premi A-E, premi finali)
- [ ] Eseguire `php artisan concorso:generate-slots` per generare i 104 winning slot

### Mail catcher
- [ ] Avviare Mailpit: `docker run -d -p 8025:8025 -p 2525:1025 --name mailpit axllent/mailpit`
- [ ] Verificare che `.env` abbia `MAIL_HOST=host.docker.internal` e `MAIL_PORT=2525`
- [ ] **Oppure**: impostare `MAIL_MAILER=log` e leggere le email in `storage/logs/laravel.log`

### Build assets
- [ ] Eseguire `docker exec mokador-concorso npm run build`

---

## 8.1 Flusso utente — Registrazione e verifica email

- [ ] Visitare `/` — homepage visibile con info concorso
- [ ] Visitare `/registrati` — compilare form con dati validi (≥ 18 anni)
- [ ] Verificare redirect post-registrazione con messaggio di conferma
- [ ] Verificare ricezione email di verifica (Mailpit su `localhost:8025` oppure log)
- [ ] Cliccare link di verifica email → redirect a `/login` con messaggio "Email verificata"
- [ ] Tentare registrazione con stessa email → errore "email già in uso"
- [ ] Tentare registrazione con data nascita < 18 anni → errore validazione

---

## 8.2 Flusso utente — Login

- [ ] Login con credenziali corrette → redirect a `/gioca-ora`
- [ ] Login con credenziali errate → messaggio errore
- [ ] Login con utente non verificato → redirect a pagina "verifica email"
- [ ] Tentare accesso a `/gioca-ora` senza login → redirect a `/login`
- [ ] Verificare che Turnstile (captcha) sia presente e funzionante sul form

---

## 8.3 Flusso utente — Giocata instant win

- [ ] Accedere a `/gioca-ora` da loggati
- [ ] Selezionare punto vendita e caricare foto scontrino (JPG o PNG, < 6MB)
- [ ] Inviare giocata → redirect a `/loading` (animazione)
- [ ] Redirect automatico a `/hai-vinto` (se vincita) o `/non-hai-vinto` (se non vincita)
- [ ] Se vincita: verificare ricezione email di notifica vincita
- [ ] Tentare seconda giocata nello stesso giorno → messaggio "Hai già giocato oggi"
- [ ] Tentare upload file > 6MB → errore validazione
- [ ] Tentare upload file non immagine (es. PDF) → errore validazione

---

## 8.4 Punti vendita

- [ ] Visitare `/punti-vendita` — lista/mappa visibile
- [ ] Verificare ricerca punti vendita funzionante

---

## 8.5 Backoffice admin — Accesso

- [ ] Accedere a `/admin` con credenziali admin (`admin@mokador.it` / `changeme123`)
- [ ] Verificare che credenziali utente normali NON accedano a `/admin`
- [ ] Verificare dashboard con widget: statistiche premi, giocate recenti, vincite

---

## 8.6 Backoffice admin — Gestione risorse

### Punti vendita (StoreResource)
- [ ] Creare un nuovo punto vendita
- [ ] Modificare un punto vendita esistente
- [ ] Disattivare un punto vendita

### Giocate (PlayResource)
- [ ] Visualizzare lista giocate con filtri
- [ ] Visualizzare dettaglio giocata con immagine scontrino
- [ ] Bannare una giocata vincente → verificare che lo slot winning venga liberato
- [ ] Sbannare una giocata → verificare che il premio NON venga riassegnato

### Utenti (UserResource)
- [ ] Visualizzare lista utenti
- [ ] Bannare un utente con motivazione
- [ ] Sbannare un utente
- [ ] Verificare che utente bannato non possa giocare

### Winning Slots (WinningSlotResource)
- [ ] Visualizzare lista slot con filtri (data, premio, assegnato/non assegnato)
- [ ] Verificare che sia in sola lettura (nessuna azione di modifica)

---

## 8.7 Estrazione finale (Fase 6)

- [ ] Accedere alla pagina estrazione finale da `/admin`
- [ ] Verificare che estrazione sia bloccata se il concorso è ancora in corso
- [ ] Modificare `CONCORSO_END_DATE` a una data passata per sbloccare l'estrazione
- [ ] Eseguire estrazione vincitori (3 vincitori)
- [ ] Eseguire estrazione sostituti (9 sostituti, 3 per premio)
- [ ] Verificare che nessun utente appaia più di una volta
- [ ] Esportare verbale notarile e verificare contenuto
- [ ] Testare reset sostituti e reset completo

---

## 8.8 Simulazione multi-giorno

Per testare più giorni di gioco senza aspettare:

```bash
# Cancella la giocata di oggi per l'utente 1 (per rigiocare)
docker exec mokador-concorso php artisan tinker --execute "
App\Models\Play::where('user_id', 1)->whereDate('played_at', today())->delete();
echo 'Giocata eliminata';
"
```

- [ ] Verificare che dopo la cancellazione l'utente possa rigiocare
- [ ] Ripetere il flusso giocata per verificare vincita/non vincita su più tentativi
- [ ] Verificare vincolo PV: stesso PV non vince 2 volte nella stessa settimana

---

## 8.9 Configurazione simil-produzione (opzionale)

Per testare in modo più fedele alla produzione:

- [ ] Impostare `APP_ENV=production` e `APP_DEBUG=false`
- [ ] Verificare che la pagina 404 custom funzioni
- [ ] Verificare che gli errori non mostrino stack trace
- [ ] Eseguire `php artisan config:cache && php artisan route:cache && php artisan view:cache`
- [ ] Sostituire Turnstile test keys con chiavi reali (se disponibili)

---

## 8.10 Rate limiting

- [ ] Tentare più di 10 login in 1 minuto → errore 429 (rate limit `auth`)
- [ ] Tentare più di 5 giocate in 1 minuto → errore 429 (rate limit `play`)

---

## Note

- Le Turnstile test keys (`1x00000000...`) accettano sempre — per test reale servono chiavi di produzione
- Il mail catcher Mailpit è l'opzione più comoda per verificare le email senza configurare SMTP reale
- Dopo il test, ricordarsi di ripristinare le date originali del concorso nel `.env`
