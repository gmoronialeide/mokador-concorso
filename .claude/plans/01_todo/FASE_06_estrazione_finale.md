# FASE 6 - Estrazione Finale Premi

**Priorità:** ALTA
**Dipendenze:** Fasi 1-5
**Stima effort:** Medio
**Stato:** ✅ COMPLETATA

---

## Contesto

Dopo la chiusura del concorso (17 maggio 2026), entro il 12 giugno va effettuata l'estrazione finale di **3 premi grandi** tra tutti i partecipanti validi. L'estrazione è pesata: chi ha giocato più volte ha più probabilità di essere estratto. Anche chi ha vinto premi settimanali (instant win) può partecipare e vincere.

**Ordine di estrazione in 2 fasi:**
1. **Fase 1 — Vincitori**: si estraggono i 3 vincitori dei 3 premi (uno alla volta, in ordine di posizione)
2. **Fase 2 — Sostituti**: per ciascun premio, si estraggono 3 sostituti (in ordine di posizione del premio)

Una persona estratta (come vincitore o sostituto) non può essere estratta nuovamente per altri premi/ruoli.

---

## 6.1 Database — Migration e Model

### Migration: `create_final_prizes_table`
- [x] Tabella `final_prizes`
  - `id` — bigIncrements
  - `name` — string (nome del premio finale)
  - `description` — text, nullable
  - `value` — decimal(10,2)
  - `position` — unsignedTinyInteger (1, 2, 3 — ordine estrazione)
  - `drawn_at` — timestamp, nullable (null = non ancora estratto)
  - `drawn_by` — foreignId → admins, nullable (chi ha effettuato l'estrazione)
  - `timestamps`

### Migration: `create_final_draw_results_table`
- [x] Tabella `final_draw_results`
  - `id` — bigIncrements
  - `final_prize_id` — foreignId → final_prizes
  - `user_id` — foreignId → users
  - `role` — enum('winner', 'substitute') — vincitore o sostituto
  - `substitute_position` — unsignedTinyInteger, nullable (1, 2, 3 per sostituti, null per vincitore)
  - `total_plays` — unsignedInteger (giocate valide dell'utente al momento dell'estrazione, per audit)
  - `drawn_at` — timestamp (momento esatto dell'estrazione)
  - `timestamps`
  - Unique constraint su `(final_prize_id, role, substitute_position)` — un solo vincitore e un sostituto per posizione per premio
  - Unique constraint su `user_id` — ogni utente può apparire una sola volta in tutta la tabella

### Model: `FinalPrize`
- [x] Campi: name, description, value, position, drawn_at, drawn_by
- [x] Relazioni:
  - `hasMany(FinalDrawResult)` — via `results()`
  - `belongsTo(Admin, 'drawn_by')` — via `admin()`
  - `winner()` → hasOne FinalDrawResult where role=winner
  - `substitutes()` → hasMany FinalDrawResult where role=substitute, orderBy substitute_position
- [x] Accessor: `is_drawn` → `drawn_at !== null`, `has_substitutes`
- [x] Cast: `drawn_at` → datetime, `value` → decimal:2, `position` → integer

### Model: `FinalDrawResult`
- [x] Campi: final_prize_id, user_id, role, substitute_position, total_plays, drawn_at
- [x] Relazioni:
  - `belongsTo(FinalPrize)`
  - `belongsTo(User)`
- [x] Cast: `substitute_position` → integer, `total_plays` → integer, `drawn_at` → datetime

### Seeder: `FinalPrizeSeeder`
- [x] Inserire i 3 premi finali (nomi/valori da definire con il cliente, placeholder per ora):
  - Posizione 1: "Premio Finale 1° — Soggiorno Vacanza" — valore TBD
  - Posizione 2: "Premio Finale 2° — Weekend Benessere" — valore TBD
  - Posizione 3: "Premio Finale 3° — Cesto Mokador Premium" — valore TBD
- [x] Aggiungere la chiamata in `DatabaseSeeder`

---

## 6.2 Service — `FinalDrawService`

### Localizzazione: `app/Services/FinalDrawService.php`

### Metodo: `getEligibleUsers(): Collection`
- [x] Recupera tutti gli utenti che:
  1. `is_banned = false` (utente non bannato)
  2. Hanno almeno 1 giocata con `is_banned = false` (giocate non bannate)
  3. **Anche vincitori di premi settimanali (instant win) sono eleggibili**
- [x] Per ogni utente calcola il conteggio giocate valide (= peso nell'estrazione)
- [x] Esclude utenti già estratti (presenti in `final_draw_results`)
- [x] Ritorna Collection di `{user_id, eligible_plays_count}`

### Metodo: `drawWinners(Admin $admin): array`
- [x] **Fase 1 — Estrazione dei 3 vincitori** (uno per premio, in ordine di posizione)
- [x] Validazione: nessun premio deve essere già estratto
- [x] Recupera utenti eleggibili con `getEligibleUsers()`
- [x] Validazione: ci devono essere almeno 12 utenti eleggibili (3 vincitori + 3×3 sostituti)
- [x] **Estrazione pesata**: per ogni utente, il numero di giocate valide è il peso
  - Esempio: utente con 10 giocate ha 10x la probabilità rispetto a utente con 1 giocata
  - Implementazione: costruire array con le somme cumulative dei pesi, generare random tra 1 e somma totale, trovare l'utente corrispondente
- [x] Wrappare tutto in `DB::transaction()` con lock pessimistico
- [x] Per ogni premio (posizione 1, 2, 3): estrarre 1 vincitore, rimuovere dal pool
- [x] Salvare i risultati in `final_draw_results` con `role=winner`
- [x] Aggiornare `drawn_at` e `drawn_by` su ciascun `FinalPrize`
- [x] Ritornare i 3 `FinalDrawResult` vincitori creati
- [x] **Logging**: registrare nel log di Laravel ogni estrazione con dettagli completi per audit

### Metodo: `drawSubstitutes(Admin $admin): array`
- [x] **Fase 2 — Estrazione dei sostituti** (3 per ciascun premio, in ordine di posizione del premio)
- [x] Validazione: tutti e 3 i vincitori devono essere già estratti
- [x] Validazione: i sostituti non devono essere già stati estratti
- [x] Recupera utenti eleggibili (esclude i 3 vincitori già estratti)
- [x] Per ogni premio (posizione 1, 2, 3): estrarre 3 sostituti con estrazione pesata, rimuovere ciascuno dal pool
- [x] Salvare in `final_draw_results` con `role=substitute` e `substitute_position` (1, 2, 3)
- [x] Ritornare i 9 `FinalDrawResult` sostituti creati
- [x] **Logging**: registrare nel log di Laravel ogni estrazione con dettagli completi per audit

### Metodo: `resetSubstitutes(): void`
- [x] Annulla solo i sostituti (fase 2)
- [x] Elimina i `FinalDrawResult` con role=substitute
- [x] Preserva i vincitori

### Metodo: `resetAll(): void`
- [x] Permette di annullare l'intera estrazione (in caso di errore)
- [x] Elimina tutti i `FinalDrawResult`
- [x] Resetta `drawn_at` e `drawn_by` su tutti i `FinalPrize`

---

## 6.3 Filament Page — Estrazione Finale

### Localizzazione: `app/Filament/Pages/FinalDraw.php`

### Configurazione pagina
- [x] Titolo: "Estrazione Finale"
- [x] Navigation group: "Concorso"
- [x] Navigation icon: `heroicon-o-gift`
- [x] Navigation sort: dopo le risorse esistenti (sort=10)

### Sezione statistiche (in alto)
- [x] Card con totale utenti eleggibili
- [x] Card con totale giocate valide nel pool
- [x] Card con premi estratti / premi totali (es. "1/3 estratti")

### Sezione premi — Fase 1: Vincitori
- [x] Bottone "Estrai Vincitori" — estrae i 3 vincitori dei 3 premi in sequenza
- [x] Modale di conferma con riepilogo (N utenti eleggibili, peso totale)
- [x] Dopo l'estrazione, mostra per ogni premio: vincitore con nome, cognome, email, telefono, numero giocate
- [x] Feedback visivo: notifiche Filament di successo/errore

### Sezione premi — Fase 2: Sostituti
- [x] Bottone "Estrai Sostituti" — visibile solo dopo che tutti i vincitori sono estratti
- [x] Per ciascun premio (in ordine 1, 2, 3): estrae 3 sostituti
- [x] Dopo l'estrazione, mostra per ogni premio: sostituti (1°, 2°, 3°) con nome, cognome, email, telefono, numero giocate

### Azioni di annullamento
- [x] Bottone "Annulla Sostituti" — annulla solo la fase 2 (se completata)
- [x] Bottone "Annulla Tutto" — annulla vincitori e sostituti
- [x] Modale di conferma prima di ogni annullamento

### Sicurezza e UX
- [x] Verificare che il concorso sia terminato prima di permettere l'estrazione (`CONCORSO_END_DATE` passata)
- [x] Messaggio di avviso se il concorso è ancora in corso
- [x] Logging dell'azione nel log di sistema (tramite FinalDrawService)
- [x] Modale di conferma prima di ogni estrazione con riepilogo (N utenti eleggibili, peso totale)

---

## 6.4 Filament Resource — FinalPrizeResource (sola lettura)

### Lista
- [x] Colonne: Posizione, Nome, Valore, Stato (Estratto/Non estratto), Data estrazione, Vincitore
- [x] Badge colorati: verde=estratto, grigio=non estratto
- [x] Sola lettura — la gestione avviene dalla pagina Estrazione Finale

---

## 6.5 Export per verbale notarile

### Azione nella pagina Estrazione Finale
- [x] Bottone "Esporta Verbale" (visibile solo se tutti e 3 i premi estratti + sostituti)
- [x] Genera CSV (UTF-8 con BOM, separatore `;` per compatibilità Excel) con:
  - Data e ora di ogni estrazione
  - Per ogni premio: vincitore + 3 sostituti con dati anagrafici completi (nome, cognome, email, telefono, data nascita, indirizzo, città, provincia, CAP)
  - Numero totale partecipanti eleggibili
  - Numero totale giocate nel pool
  - Algoritmo utilizzato (descrizione sintetica)
  - Admin che ha effettuato l'estrazione

---

## 6.6 Testing

### Unit Test: `FinalDrawServiceTest` (21 test)
- [x] Test utenti eleggibili: esclude bannati, esclude utenti con solo giocate bannate
- [x] Test utenti eleggibili: **include** vincitori di premi settimanali (instant win)
- [x] Test utenti eleggibili: esclude utenti già estratti
- [x] Test peso estrazione: utente con più giocate ha proporzionalmente più probabilità (test statistico su 50 iterazioni)
- [x] Test fase 1: `drawWinners()` estrae esattamente 3 vincitori (uno per premio)
- [x] Test fase 1: assegnazione ai premi corretti per posizione
- [x] Test fase 1: aggiorna drawn_at e drawn_by su FinalPrize
- [x] Test fase 1: nessun utente duplicato tra vincitori
- [x] Test fase 1: fallisce se già estratti
- [x] Test fase 2: `drawSubstitutes()` fallisce se vincitori non ancora estratti
- [x] Test fase 2: `drawSubstitutes()` estrae 9 sostituti (3 per premio)
- [x] Test unicità cross-fase: nessun vincitore appare tra i sostituti
- [x] Test unicità intra-fase: stesso utente non estratto due volte né come vincitore né come sostituto
- [x] Test vincoli: almeno 12 utenti eleggibili richiesti (3 vincitori + 9 sostituti)
- [x] Test fase 2: fallisce se sostituti già estratti
- [x] Test conteggio giocate valide corretto (esclude bannate)
- [x] Test reset sostituti: annulla solo fase 2, preserva vincitori
- [x] Test reset tutto: annulla entrambe le fasi, resetta drawn_at/drawn_by
- [x] Test total_plays registrato per audit

### Feature Test: `FinalDrawPageTest` (13 test)
- [x] Pagina richiede autenticazione admin
- [x] Pagina accessibile da admin autenticato
- [x] Bottone "Estrai Vincitori" nascosto se concorso in corso
- [x] Bottone "Estrai Vincitori" visibile se concorso terminato
- [x] Bottone "Estrai Sostituti" nascosto se vincitori non estratti
- [x] Fase 1 crea correttamente 3 vincitori
- [x] Fase 2 crea correttamente 9 sostituti
- [x] Dopo estrazione completa, risultati visibili nella pagina
- [x] Annulla Tutto resetta vincitori e premi
- [x] Annulla Sostituti preserva vincitori
- [x] Export Verbale nascosto se non completamente estratto
- [x] Export Verbale visibile quando estrazione completa
- [x] Avviso concorso in corso visibile

---

## 6.7 Algoritmo di estrazione pesata — Dettaglio tecnico

```
Funzione: estraiUno(pool)
  Input: lista utenti con peso (= numero giocate valide)
  Output: 1 utente estratto

  1. Calcola somma_totale = Σ pesi
  2. Genera random R = random_int(1, somma_totale)
  3. Per ogni utente in ordine:
     a. somma_cumulativa += peso_utente
     b. Se somma_cumulativa >= R → utente estratto
  4. Rimuovi utente estratto dal pool

Fase 1 — Vincitori (3 estrazioni):
  pool = tutti gli utenti eleggibili
  Per premio in [1, 2, 3]:
    vincitore = estraiUno(pool)  → salva come winner del premio

Fase 2 — Sostituti (9 estrazioni):
  pool = utenti eleggibili MENO i 3 vincitori già estratti
  Per premio in [1, 2, 3]:
    Per posizione_sostituto in [1, 2, 3]:
      sostituto = estraiUno(pool)  → salva come substitute del premio
```

**Note**:
- Si usa `random_int()` (CSPRNG) per garanzia crittografica di casualità, come richiesto per i concorsi a premi in Italia
- Vincitori di premi settimanali (instant win) restano nel pool e possono vincere anche l'estrazione finale

---

## Note

- I nomi e i valori dei 3 premi finali sono placeholder — da confermare con il cliente
- L'estrazione deve avvenire dopo la chiusura del concorso (17 maggio 2026)
- Potrebbe essere necessaria la presenza di un notaio/funzionario Camera di Commercio — il sistema fornisce lo strumento, l'estrazione verrà effettuata alla loro presenza
- L'export del verbale serve come documentazione ufficiale dell'estrazione
