# FASE 1 - Setup Progetto Laravel + Filament

**Priorità:** CRITICA
**Dipendenze:** Nessuna
**Stato:** COMPLETATA

---

## 1.1 Inizializzazione Laravel 12

- [x] Creare progetto Laravel 12 tramite Composer (v12.54.1)
- [x] Configurare `.env` (DB MySQL su database-condiviso, APP_URL = invacanza.mokador.it, APP_NAME, MAIL, ecc.)
- [x] Configurare `config/database.php` per MySQL (charset `utf8mb4`, collation `utf8mb4_unicode_ci`)
- [x] Configurare timezone Italia (`Europe/Rome`) in `config/app.php`
- [x] Configurare locale `it` in `config/app.php`
- [x] Configurare filesystem disk `receipts` per upload scontrini in `config/filesystems.php`
- [x] Aggiungere `.env.example` con tutti i parametri necessari documentati

## 1.2 Installazione Filament

- [x] Installare Filament 3 via Composer (v3.3.49 - compatibile con Laravel 12)
- [x] Configurare Filament Panel per backoffice su path `/admin`
- [x] Configurare Guard `admin` separato (non condivide autenticazione con utenti frontend)
- [x] Creare Admin model separato da User (`app/Models/Admin.php`)
- [x] Configurare tema/colori Filament (palette Mokador: primary #9D4A15, gray #4F3328)

## 1.3 Struttura directory assets

- [x] Copiare gli assets statici esistenti (`css/`, `js/`, `img/`) nella struttura Laravel `public/`
- [x] HTML statici mantenuti in `public/static/` come riferimento per i Blade templates
- [x] Vite presente ma non necessario per bundling (CSS/JS già pronti)

## 1.4 Configurazione ambiente

- [x] CSRF protection attiva (default Laravel)
- [x] Configurare rate limiting in `bootstrap/app.php` (auth: 10/min, play: 5/min)
- [x] Session driver: database
- [x] Queue driver: database
- [x] Logging: daily + canale `concorso` dedicato per le giocate (90 giorni retention)

## 1.5 Database e Seeder

- [x] Creato database `mokador_concorso` su MySQL
- [x] Migration tabella `admins` eseguita
- [x] Migration tabelle default Laravel (users, sessions, cache, jobs) eseguite
- [x] AdminSeeder: admin di default creato (admin@mokador.it)

---

## Note

- Il progetto Laravel è stato creato nella root integrando i file statici esistenti
- Gli HTML statici in `public/static/` servono come riferimento per i Blade templates (Fase 4)
- MySQL 8.0 in uso (database-condiviso), ma codice compatibile MySQL 5.7
- Filament 3.x (non 5) è la versione corrente compatibile con Laravel 12
