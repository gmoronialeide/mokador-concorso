# Production TODO — Mokador Concorso

> Stato progetto: tutte le fasi funzionali (1-6) sono **complete**.
> Mancano configurazioni, documenti legali e infrastruttura per andare live.
> Contest: 20 aprile – 17 maggio 2026 | Estrazione finale: entro 12 giugno 2026

---

## 🔴 BLOCCANTI (senza questi non si va in produzione)

### Documenti legali
- [x] ~~Privacy Policy~~ → route `/privacy` punta a LegalBlink (esterno, target blank)
- [x] ~~Regolamento concorso PDF~~ → `public/pdf/regolamento.pdf`, route `/regolamento` (target blank)
- [x] ~~Cookie Policy~~ → route `/cookie-policy` punta a LegalBlink (esterno, target blank)
- [ ] **Banner cookie GDPR** → serve un banner di consenso cookie (es. Iubenda, Cookiebot, o lo script di LegalBlink) dato che GTM è attivo

### Configurazione SMTP
- [x] ~~Credenziali SMTP produzione~~ → configurate in `.env.production` (Aruba: `vu000671.arubabiz.net:587`, TLS, self-signed)
- [x] ~~Stream SSL options~~ → `config/mail.php` configurato con `verify_peer: false`, `allow_self_signed: true`
- [ ] **Sender verificato** → assicurarsi che `concorso@mokador.it` sia autorizzato sul mail server (SPF, DKIM, DMARC)
- [ ] **Test invio email** → verificare che arrivino: email di verifica, notifica vincita, reset password

### SSL / HTTPS
- [ ] **Certificato SSL** per `invacanza.mokador.it` (Let's Encrypt o certificato del provider)
- [x] ~~Force HTTPS~~ → `APP_URL=https://invacanza.mokador.it` + `SESSION_SECURE_COOKIE=true` già in `.env.production`
- [ ] Verificare redirect HTTP → HTTPS (a livello di reverse proxy o Nginx)

### Variabili ambiente produzione (.env)
- [x] ~~`APP_ENV=production`~~ → già in `.env.production`
- [x] ~~`APP_DEBUG=false`~~ → già in `.env.production`
- [ ] `APP_KEY` → generare nuova key (`php artisan key:generate`)
- [x] ~~`DB_*`~~ → credenziali MySQL già in `.env.production`
- [ ] `ADMIN_PASSWORD` → cambiare da `changeme123` a password forte
- [x] ~~`SESSION_SECURE_COOKIE=true`~~ → già in `.env.production`
- [x] ~~`LOG_LEVEL=warning`~~ → già in `.env.production`

### Cloudflare Turnstile
- [x] ~~Chiavi produzione~~ → già in `.env.production`

### Database produzione
- [ ] Eseguire `php artisan migrate` in produzione
- [ ] Eseguire `php artisan db:seed` (crea admin + 5 premi + 3 premi finali + schedule)
- [ ] **Importare lista punti vendita** (via Filament admin o migration dedicata)
- [ ] Eseguire `php artisan concorso:generate-slots` per generare i 104 slot vincenti

---

## 🟡 IMPORTANTI (fortemente consigliati prima del lancio)

### Analytics & Tracking
- [x] ~~Google Tag Manager (GTM)~~ → integrato in `layouts/app.blade.php` (solo `@production`, `GTM-PRL8WFGW`)
- [ ] **Google Analytics 4** → configurare via GTM
- [ ] Tracking eventi: registrazione, verifica email, giocata, vincita
- [ ] Conversion tracking per funnel registrazione → giocata

### Monitoraggio & Errori
- [ ] **Error tracking** → integrare Sentry, Bugsnag o Rollbar (pacchetto Laravel disponibile)
- [ ] **Uptime monitoring** → servizio esterno (UptimeRobot, Pingdom, ecc.) su `invacanza.mokador.it`
- [ ] **Health check** → la route `/up` di Laravel è già disponibile, collegarla al monitoring

### Backup
- [ ] **Backup database automatico** → cron job giornaliero (mysqldump o `spatie/laravel-backup`)
- [ ] **Backup file ricevute** → `storage/app/private/receipts/` va backuppato
- [ ] Definire retention policy (almeno 90 giorni post-concorso per obblighi legali)
- [ ] Testare procedura di restore

### Queue Worker
- [x] ~~Queue worker in supervisord~~ → aggiunto `[program:queue-worker]` in `docker/supervisord.conf`
- [x] ~~Modalità sync~~ → `.env.production` impostato su `QUEUE_CONNECTION=sync` (soluzione pragmatica, nessun worker necessario)

### Pagine di errore
- [x] ~~500.blade.php~~ → pagina errore server branded
- [x] ~~503.blade.php~~ → pagina maintenance mode branded
- [x] ~~419.blade.php~~ → pagina sessione scaduta branded

---

## 🟢 NICE TO HAVE (miglioramenti post-lancio)

### Performance
- [ ] Valutare **Redis** per cache/session/queue (al posto di database driver)
- [ ] **CDN** per asset statici (CSS, JS, immagini)
- [ ] Ottimizzazione immagini (WebP, lazy loading)
- [ ] `php artisan config:cache`, `route:cache`, `view:cache` in produzione

### CI/CD
- [ ] Pipeline GitHub Actions: test automatici su push
- [ ] Deploy automatico (o semi-automatico) su merge in `main`
- [ ] Strategia di rollback documentata

### SEO
- [ ] `sitemap.xml` (anche minimale)
- [ ] Meta tags Open Graph per condivisione social
- [ ] Aggiornare `robots.txt` se servono esclusioni

### Scheduled Tasks
- [ ] Cron per pulizia sessioni scadute
- [ ] Cron per retry email fallite nella coda
- [ ] Reminder: aggiungere `php artisan schedule:run` al crontab di produzione se si aggiungono task schedulati

### Varie
- [ ] **Favicon** → verificare che sia presente e corretto
- [ ] **Meta description** per le pagine principali
- [ ] Test di carico (load test) con numero stimato di utenti concorrenti
- [ ] Documentazione procedura di estrazione finale per il notaio

---

## Riepilogo stato fasi

| Fase | Descrizione | Stato |
|------|-------------|-------|
| 01 | Setup progetto & Docker | ✅ Completa |
| 02 | Database & Models | ✅ Completa |
| 03 | Algoritmo Instant Win | ✅ Completa |
| 04 | Frontend Blade | ✅ Completa |
| 05 | Backoffice Filament | ✅ Completa |
| 06 | Estrazione Finale | ✅ Completa |
| 07 | Testing & Deploy | 🔄 Parziale (config produzione) |
| 08 | Test End-to-End | ✅ Completa |
