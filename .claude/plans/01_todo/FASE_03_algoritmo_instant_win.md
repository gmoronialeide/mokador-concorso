# FASE 3 - Algoritmo Instant Win

**Priorità:** CRITICA
**Dipendenze:** Fase 2
**Stima effort:** Fase più delicata, richiede attenzione e testing
**Stato:** ✅ COMPLETATA

---

## 3.1 Generazione Slot Vincenti (Artisan Command)

**Command:** `php artisan concorso:generate-slots`

- [x] Calcolare i 28 giorni del concorso (20 aprile - 17 maggio 2026)
- [x] Per ogni giorno, determinare il giorno della settimana
- [x] Applicare la programmazione premi da regolamento:

| Premio | Lun | Mar | Mer | Gio | Ven | Sab | Dom | Totale/sett | Totale 4 sett |
|--------|-----|-----|-----|-----|-----|-----|-----|-------------|---------------|
| A      | 1   | 1   | 1   | 1   | 1   | 1   | 1   | 7           | 28            |
| B      | 1   | 1   | 1   | 1   | 1   | 1   | 1   | 7           | 28            |
| C      | 1   | -   | 1   | -   | 1   | 1   | 1   | 5           | 20            |
| D      | -   | 1   | 1   | 1   | -   | 1   | -   | 4           | 16            |
| E      | 1   | -   | -   | 1   | -   | 1   | -   | 3           | 12            |

- [x] Per ogni slot, generare un'ora casuale tra le 08:00 e le 22:00 (orario realistico di partecipazione)
- [x] Salvare tutti i 104 record nella tabella `winning_slots`
- [x] Aggiungere opzione `--dry-run` per verifica senza inserimento
- [x] Aggiungere opzione `--reset` per rigenerare (solo prima dell'inizio concorso)
- [x] Log della generazione per audit

### Verifica calendario concorso
```
20 apr 2026 = Lunedì    → Settimana 1: Lun 20 - Dom 26
27 apr 2026 = Lunedì    → Settimana 2: Lun 27 - Dom 3 mag
 4 mag 2026 = Lunedì    → Settimana 3: Lun 4  - Dom 10
11 mag 2026 = Lunedì    → Settimana 4: Lun 11 - Dom 17
```
Perfetto: 4 settimane esatte da lunedì a domenica.

---

## 3.2 InstantWinService

**Classe:** `App\Services\InstantWinService`

### Metodo principale: `attempt(Play $play): ?Prize`

- [x] Implementare metodo `attempt(Play $play): ?Prize`
- [x] Implementare "Regola ore 12": dopo le 12:00, se ci sono slot non assegnati per il giorno, il primo giocatore valido vince (ignora scheduled_time)
- [x] Usare `DB::transaction()` con isolation level adeguato
- [x] Usare `lockForUpdate()` (Eloquent) per lock pessimistico
- [x] Gestire correttamente le eccezioni (rollback automatico)
- [x] Loggare ogni tentativo (vincente e non) per audit/debug

### Metodo: `getWeekBounds(string $date): array`
- [x] Data una data, restituisce [lunedì, domenica] della settimana del concorso
- [x] Serve per il vincolo 1 premio/punto vendita/settimana

### Metodo: `hasStoreWonThisWeek(string $storeCode, string $date): bool`
- [x] Query su `plays` WHERE store_code AND is_winner AND played_at BETWEEN week bounds
- [x] Usata nel flusso principale per il vincolo punto vendita

---

## 3.3 ~~Recovery Slot Non Assegnati~~ — RIMOSSO

**Regola:** i premi NON assegnati in una giornata NON vengono mai riassegnati nei giorni successivi.
Il command `concorso:recover-slots` è stato eliminato. Gli slot non vinti restano non assegnati.

---

## 3.4 Testing dell'algoritmo

- [x] **Test unitario:** generazione slot produce esattamente 104 record con distribuzione corretta
- [x] **Test unitario:** vincita assegna correttamente lo slot e aggiorna play
- [x] **Test unitario:** non vincita quando nessuno slot disponibile
- [x] **Test unitario:** vincolo punto vendita rispettato (max 1/settimana)
- [x] **Test unitario:** vincolo PV: giocata da PV che ha già vinto nella settimana NON vince, ma lo slot resta disponibile per altri
- [x] **Test regola ore 12:** prima delle 12, slot con scheduled_time futuro NON viene assegnato
- [x] **Test regola ore 12:** dopo le 12, slot non assegnato viene dato al primo giocatore valido (ignora scheduled_time)
- [x] **Test regola ore 12 + vincolo PV:** dopo le 12, se il PV ha già vinto, la giocata NON vince e lo slot resta per il prossimo
- [x] **Test concorrenza:** due giocate simultanee non vincono lo stesso slot (lock)
- [x] **Test scenario molti giocatori:** non vengono assegnati più premi del previsto

---

## 3.5 Dichiarazione Tecnica

Il regolamento richiede una "dichiarazione sostitutiva di atto notorio rilasciata dal responsabile tecnico" per il software di estrazione.

- [x] Preparare documentazione tecnica dell'algoritmo: `docs/dichiarazione_tecnica_algoritmo.md`
  - Metodo di generazione casuale (PHP `random_int()` - CSPRNG)
  - Pre-generazione slot con distribuzione temporale casuale
  - Lock pessimistico per concorrenza
  - Non determinabilità a priori dei momenti vincenti
  - Tracciabilità e audit logging

---

## Note sulla sicurezza dell'algoritmo

1. **`random_int()`** (non `rand()` o `mt_rand()`): usa il CSPRNG del sistema operativo
2. **Lock pessimistico** (`SELECT ... FOR UPDATE`): previene race condition
3. **Transazione atomica**: impossibile assegnare parzialmente un premio
4. **Slot pre-generati**: i momenti vincenti esistono prima delle giocate, conformi al regolamento
5. **Log completo**: ogni operazione è tracciabile per verifica notarile

## Note implementative

- Config `app.concorso_start_date` e `app.concorso_end_date` in `config/app.php` (lette da .env)
- Usare `whereDate()` per confronti date (compatibilità SQLite/MySQL)
- Model defaults `$attributes` per booleani (evita NULL su SQLite)
