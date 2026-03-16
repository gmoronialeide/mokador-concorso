# FASE 3 - Algoritmo Instant Win

**Priorità:** CRITICA
**Dipendenze:** Fase 2
**Stima effort:** Fase più delicata, richiede attenzione e testing

---

## 3.1 Generazione Slot Vincenti (Artisan Command)

**Command:** `php artisan concorso:generate-slots`

- [ ] Calcolare i 28 giorni del concorso (20 aprile - 17 maggio 2026)
- [ ] Per ogni giorno, determinare il giorno della settimana
- [ ] Applicare la programmazione premi da regolamento:

| Premio | Lun | Mar | Mer | Gio | Ven | Sab | Dom | Totale/sett | Totale 4 sett |
|--------|-----|-----|-----|-----|-----|-----|-----|-------------|---------------|
| A      | 1   | 1   | 1   | 1   | 1   | 1   | 1   | 7           | 28            |
| B      | 1   | 1   | 1   | 1   | 1   | 1   | 1   | 7           | 28            |
| C      | 1   | -   | 1   | -   | 1   | 1   | 1   | 5           | 20            |
| D      | -   | 1   | 1   | 1   | -   | 1   | -   | 4           | 16            |
| E      | 1   | -   | -   | 1   | -   | 1   | -   | 3           | 12            |

- [ ] Per ogni slot, generare un'ora casuale tra le 08:00 e le 22:00 (orario realistico di partecipazione)
- [ ] Salvare tutti i 104 record nella tabella `winning_slots`
- [ ] Aggiungere opzione `--dry-run` per verifica senza inserimento
- [ ] Aggiungere opzione `--reset` per rigenerare (solo prima dell'inizio concorso)
- [ ] Log della generazione per audit

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

Flusso:

```
1. Ricevi la giocata appena registrata
2. Determina data e ora corrente
3. Apri transazione DB con lock
4. Cerca slot vincenti disponibili:
   - scheduled_date = oggi
   - scheduled_time <= ora corrente
   - is_assigned = false
   - ORDER BY scheduled_time ASC (primo slot disponibile)
   - FOR UPDATE (lock pessimistico per concorrenza)
5. Se nessuno slot trovato → return null (non vince)
6. Verifica vincolo punto vendita:
   - Il punto vendita del giocatore ha già vinto questa settimana?
   - Se sì → return null (lo slot resta disponibile per altri)
7. Assegna lo slot:
   - winning_slot.is_assigned = true
   - winning_slot.play_id = play.id
   - winning_slot.assigned_at = now()
   - play.is_winner = true
   - play.prize_id = slot.prize_id
   - play.winning_slot_id = slot.id
8. Commit transazione
9. Return Prize
```

- [ ] Implementare metodo `attempt(Play $play): ?Prize`
- [ ] Usare `DB::transaction()` con isolation level adeguato
- [ ] Usare `lockForUpdate()` (Eloquent) per lock pessimistico
- [ ] Gestire correttamente le eccezioni (rollback automatico)
- [ ] Loggare ogni tentativo (vincente e non) per audit/debug

### Metodo: `getWeekBounds(string $date): array`
- [ ] Data una data, restituisce [lunedì, domenica] della settimana del concorso
- [ ] Serve per il vincolo 1 premio/punto vendita/settimana

### Metodo: `hasStoreWonThisWeek(string $storeCode, string $date): bool`
- [ ] Query su `plays` WHERE store_code AND is_winner AND played_at BETWEEN week bounds
- [ ] Usata nel flusso principale per il vincolo punto vendita

---

## 3.3 Recovery Slot Non Assegnati

**Command:** `php artisan concorso:recover-slots`
**Scheduling:** Eseguire ogni notte alle 00:05 (o via cron manuale)

Logica:
- [ ] Trovare tutti gli slot di ieri (e giorni precedenti) non assegnati
- [ ] Spostarli al giorno corrente con nuova ora casuale (08:00 - 22:00)
- [ ] Se il giorno corrente è oltre la fine del concorso, NON spostare (premi non assegnati = regolamento)
- [ ] Loggare ogni spostamento per audit
- [ ] Rispettare comunque la programmazione per tipo di premio e giorno della settimana:
  - Se un Premio C non assegnato di lunedì viene spostato a martedì (giorno in cui C non è previsto), lo si sposta comunque perché è un recovery (il regolamento prevede l'assegnazione di tutti i premi)

### Alternativa senza cron
- [ ] Se il server non supporta cron: implementare il recovery come check all'interno di `InstantWinService::attempt()`, eseguito una volta al giorno (flag in cache/DB)

---

## 3.4 Testing dell'algoritmo

- [ ] **Test unitario:** generazione slot produce esattamente 104 record con distribuzione corretta
- [ ] **Test unitario:** vincita assegna correttamente lo slot e aggiorna play
- [ ] **Test unitario:** non vincita quando nessuno slot disponibile
- [ ] **Test unitario:** vincolo punto vendita rispettato (max 1/settimana)
- [ ] **Test concorrenza:** due giocate simultanee non vincono lo stesso slot (lock)
- [ ] **Test recovery:** slot non assegnati vengono correttamente spostati
- [ ] **Test scenario pochi giocatori:** tutti i premi vengono comunque distribuiti nel tempo
- [ ] **Test scenario molti giocatori:** non vengono assegnati più premi del previsto

---

## 3.5 Dichiarazione Tecnica

Il regolamento richiede una "dichiarazione sostitutiva di atto notorio rilasciata dal responsabile tecnico" per il software di estrazione.

- [ ] Preparare documentazione tecnica dell'algoritmo:
  - Metodo di generazione casuale (PHP `random_int()` - CSPRNG)
  - Pre-generazione slot con distribuzione temporale casuale
  - Lock pessimistico per concorrenza
  - Meccanismo di recovery
  - Non determinabilità a priori dei momenti vincenti

---

## Note sulla sicurezza dell'algoritmo

1. **`random_int()`** (non `rand()` o `mt_rand()`): usa il CSPRNG del sistema operativo
2. **Lock pessimistico** (`SELECT ... FOR UPDATE`): previene race condition
3. **Transazione atomica**: impossibile assegnare parzialmente un premio
4. **Slot pre-generati**: i momenti vincenti esistono prima delle giocate, conformi al regolamento
5. **Log completo**: ogni operazione è tracciabile per verifica notarile
