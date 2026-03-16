# FASE 6 - Testing, Sicurezza e Deploy

**Priorità:** ALTA
**Dipendenze:** Fasi 1-5
**Stima effort:** Fase finale

---

## 6.1 Testing

### Test Algoritmo Instant Win (Priorità massima)
- [ ] Test generazione 104 slot con distribuzione corretta per giorno/premio
- [ ] Test vincita: slot disponibile viene assegnato
- [ ] Test non vincita: nessuno slot disponibile
- [ ] Test vincolo punto vendita: max 1 premio/settimana/PV
- [ ] Test concorrenza: lock pessimistico previene doppia assegnazione
- [ ] Test recovery: slot non assegnati vengono recuperati
- [ ] Test confini temporali: giocata prima delle 08:00, dopo le 22:00
- [ ] Test date: giocata fuori periodo concorso rifiutata

### Test Autenticazione
- [ ] Registrazione con dati validi
- [ ] Registrazione con email duplicata → errore
- [ ] Registrazione minorenni → errore
- [ ] Login valido
- [ ] Login errato
- [ ] Accesso pagina gioco senza login → redirect login
- [ ] Utente bannato non può giocare

### Test Giocata
- [ ] Upload scontrino JPG valido
- [ ] Upload scontrino PNG valido
- [ ] Upload file > 6MB → errore
- [ ] Upload file non immagine → errore
- [ ] Codice punto vendita inesistente → errore
- [ ] Punto vendita disattivato → errore
- [ ] Seconda giocata nello stesso giorno → errore
- [ ] Giocata fuori periodo concorso → errore

### Test Backoffice
- [ ] CRUD punti vendita
- [ ] Ban giocata vincente: slot viene liberato
- [ ] Ban utente: non può più giocare
- [ ] Import CSV punti vendita
- [ ] Visualizzazione scontrino da admin

---

## 6.2 Sicurezza

- [ ] **CSRF**: attivo su tutti i form (default Laravel)
- [ ] **XSS**: escape output con `{{ }}` Blade (default)
- [ ] **SQL Injection**: uso esclusivo di Eloquent/query builder parametrizzato
- [ ] **Upload sicuro**:
  - Validazione MIME type reale (non solo estensione)
  - Rinomina file con hash
  - Salvataggio fuori da document root (`storage/app/private/`)
  - No esecuzione PHP nella cartella uploads
- [ ] **Rate limiting**:
  - 1 giocata/giorno/utente (logica applicativa)
  - Rate limit HTTP su rotte sensibili (login, registrazione)
- [ ] **Password**: bcrypt con cost factor default Laravel (10)
- [ ] **Session**:
  - HttpOnly cookies
  - Secure flag in produzione
  - Session fixation protection (regenerate on login)
- [ ] **Headers sicurezza**: X-Content-Type-Options, X-Frame-Options, CSP base
- [ ] **Admin separato**: guard diverso, nessun modo di accedere al backoffice con credenziali utente

---

## 6.3 Configurazione Server / Deploy

### Requisiti server
- [ ] PHP 8.3 con estensioni: mbstring, openssl, pdo_mysql, tokenizer, xml, ctype, json, bcmath, fileinfo, gd (per immagini)
- [ ] MySQL 5.7.44
- [ ] Composer 2.x
- [ ] Accesso SSH (per artisan commands)
- [ ] Cron job (per scheduler Laravel o recovery slot)
- [ ] SMTP configurato (per email vincita)

### Procedura deploy
- [ ] Upload codice (git pull o rsync, esclusi: .env, storage/app, node_modules, vendor)
- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] `php artisan migrate --force`
- [ ] `php artisan config:cache`
- [ ] `php artisan route:cache`
- [ ] `php artisan view:cache`
- [ ] `php artisan storage:link`
- [ ] Verificare permessi cartella `storage/` e `bootstrap/cache/`
- [ ] Configurare virtual host Apache/Nginx con document root su `public/`

### Cron job
- [ ] Aggiungere al crontab: `* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1`
- [ ] Nello scheduler Laravel:
  - `concorso:recover-slots` → daily at 00:05
  - `queue:work --stop-when-empty` → every minute (se queue driver = database)

### Pre-lancio (prima del 20 aprile 2026)
- [ ] Eseguire `php artisan concorso:generate-slots` per generare i 104 slot vincenti
- [ ] Eseguire `php artisan db:seed --class=PrizeSeeder` per inserire i premi
- [ ] Eseguire `php artisan db:seed --class=AdminSeeder` per creare admin
- [ ] Caricare punti vendita (import CSV o inserimento manuale)
- [ ] Test completo end-to-end in ambiente staging
- [ ] Verificare invio email da SMTP di produzione
- [ ] Backup database automatico configurato

---

## 6.4 Post-lancio e monitoraggio

- [ ] Monitorare dashboard Filament per statistiche giornaliere
- [ ] Verificare che i premi vengano assegnati quotidianamente
- [ ] Controllare log errori (`storage/logs/laravel.log`)
- [ ] Verificare recovery slot notturno funzionante
- [ ] Dopo il 17 maggio: disabilitare giocate, mantenere accesso admin
- [ ] Entro 12 giugno: estrazione finale (funzionalità da valutare se implementare nel sistema o manuale)

---

## Note

- L'estrazione finale (3 premi grandi) potrebbe essere gestita:
  - **Nel sistema**: command artisan che estrae casualmente tra tutte le partecipazioni valide
  - **Manualmente**: export CSV partecipazioni, estrazione davanti al notaio
  - Probabilmente meglio la seconda opzione dato che richiede presenza notaio/Camera di Commercio
