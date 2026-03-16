# FASE 1 - Setup Progetto Laravel + Filament

**Priorità:** CRITICA
**Dipendenze:** Nessuna
**Stima effort:** Prima fase da completare

---

## 1.1 Inizializzazione Laravel 12

- [ ] Creare progetto Laravel 12 tramite Composer
- [ ] Configurare `.env` (DB, APP_URL = invacanza.mokador.it, APP_NAME, MAIL, ecc.)
- [ ] Configurare `config/database.php` per MySQL 5.7 (charset `utf8mb4`, collation `utf8mb4_unicode_ci`, engine `InnoDB`)
- [ ] Configurare timezone Italia (`Europe/Rome`) in `config/app.php`
- [ ] Configurare locale `it` in `config/app.php`
- [ ] Configurare filesystem disk per upload scontrini (`storage/app/receipts`) in `config/filesystems.php`
- [ ] Aggiungere `.env.example` con tutti i parametri necessari documentati

## 1.2 Installazione Filament 5

- [ ] Installare Filament 5 via Composer
- [ ] Configurare Filament Panel per backoffice su path `/admin`
- [ ] Configurare Guard separato per admin (non condivide autenticazione con utenti frontend)
- [ ] Creare AdminUser model separato da User
- [ ] Configurare tema/colori Filament (palette Mokador: marrone/arancione)

## 1.3 Struttura directory assets

- [ ] Spostare/copiare gli assets statici esistenti (`css/`, `js/`, `img/`) nella struttura Laravel `public/`
- [ ] Verificare che i path nei template siano corretti con `asset()` helper
- [ ] Configurare Vite (o escluderlo se non serve bundling, dato che CSS/JS sono già pronti)

## 1.4 Configurazione ambiente

- [ ] Configurare CSRF protection
- [ ] Configurare rate limiting in `bootstrap/app.php` o `RouteServiceProvider`
- [ ] Configurare session driver (database, per affidabilità)
- [ ] Configurare queue driver (database o sync, da decidere in base al server)
- [ ] Configurare logging (daily, canale dedicato per le giocate)

---

## Note

- Il progetto Laravel va creato nella root `mokador-concorso/` integrando i file statici esistenti
- Gli HTML statici in `public/static/` serviranno come riferimento per i Blade templates (Fase 4)
- MySQL 5.7.44: non usare features MySQL 8+ (CTE, JSON_TABLE, window functions)
