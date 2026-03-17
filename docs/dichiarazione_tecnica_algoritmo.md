# Dichiarazione Tecnica — Algoritmo di Estrazione Instant Win

**Concorso:** "Mokador ti porta in vacanza"
**Periodo:** 20 aprile 2026 – 17 maggio 2026 (28 giorni)
**Montepremi:** 104 premi totali distribuiti in 5 categorie (A–E)

---

## 1. Architettura del sistema di estrazione

Il sistema di estrazione è di tipo **Instant Win a slot temporali pre-generati**: prima dell'inizio del concorso vengono generati 104 "slot vincenti", ciascuno associato a un giorno e a un orario casuale. Quando un partecipante effettua una giocata, il sistema verifica se in quel momento esiste uno slot vincente disponibile e, in caso affermativo, lo assegna.

### 1.1 Tecnologie utilizzate

- **Linguaggio:** PHP 8.3
- **Framework:** Laravel 12
- **Database:** MySQL 8.0 con engine InnoDB (supporto transazioni ACID)
- **Generatore casuale:** `random_int()` — utilizza il CSPRNG (Cryptographically Secure Pseudo-Random Number Generator) del sistema operativo (`/dev/urandom` su Linux)

---

## 2. Pre-generazione degli slot vincenti

### 2.1 Distribuzione temporale

Gli slot vengono generati tramite il comando `php artisan concorso:generate-slots` **prima dell'inizio del concorso**. La distribuzione segue una programmazione settimanale fissa ripetuta per 4 settimane:

| Premio | Lun | Mar | Mer | Gio | Ven | Sab | Dom | Tot/sett | Tot concorso |
|--------|-----|-----|-----|-----|-----|-----|-----|----------|--------------|
| A      | 1   | 1   | 1   | 1   | 1   | 1   | 1   | 7        | 28           |
| B      | 1   | 1   | 1   | 1   | 1   | 1   | 1   | 7        | 28           |
| C      | 1   | –   | 1   | –   | 1   | 1   | 1   | 5        | 20           |
| D      | –   | 1   | 1   | 1   | –   | 1   | –   | 4        | 16           |
| E      | 1   | –   | –   | 1   | –   | 1   | –   | 3        | 12           |

### 2.2 Generazione dell'orario casuale

Per ogni slot, l'orario viene generato tramite tre chiamate indipendenti a `random_int()`:

- **Ora:** `random_int(8, 21)` — fascia 08:00–21:59
- **Minuti:** `random_int(0, 59)`
- **Secondi:** `random_int(0, 59)`

La funzione `random_int()` di PHP utilizza il generatore crittografico del sistema operativo, garantendo:

- **Non predicibilità:** l'output non è determinabile a priori
- **Uniformità:** distribuzione uniforme nell'intervallo specificato
- **Sicurezza crittografica:** resistenza ad attacchi di tipo prediction

### 2.3 Immutabilità degli slot

Una volta generati, gli slot vincenti non possono essere modificati durante il concorso. Il comando di rigenerazione (`--reset`) è bloccato se anche un solo slot risulta già assegnato.

---

## 3. Algoritmo di assegnazione (Instant Win)

### 3.1 Flusso di esecuzione

Quando un partecipante effettua una giocata, il servizio `InstantWinService::attempt()` esegue i seguenti passi:

1. **Verifica vincolo punto vendita:** controlla se il punto vendita del partecipante ha già generato una vincita nella settimana corrente (lunedì–domenica). In caso positivo, la giocata non può vincere.

2. **Apertura transazione con lock pessimistico:** viene aperta una transazione database con `SELECT ... FOR UPDATE` sugli slot candidati, impedendo accessi concorrenti.

3. **Ricerca slot disponibili:** il sistema cerca slot vincenti per la data odierna, non ancora assegnati:
   - **Prima delle 12:00** — solo slot con `scheduled_time ≤ ora corrente` (modalità normale)
   - **Dopo le 12:00** — qualsiasi slot non assegnato della giornata, indipendentemente dall'orario programmato (Regola ore 12)

4. **Assegnazione atomica:** se trovato, lo slot viene assegnato al partecipante in modo atomico (aggiornamento slot + aggiornamento giocata nella stessa transazione).

5. **Commit o rollback:** la transazione viene confermata in caso di successo o annullata automaticamente in caso di errore.

### 3.2 Regola ore 12:00

Se entro le 12:00 nessuno dei premi giornalieri è stato vinto, a partire dalle 12:00 il primo giocatore idoneo (il cui punto vendita non ha già vinto nella settimana) vince il premio. Questa regola garantisce una maggiore probabilità di assegnazione dei premi giornalieri.

### 3.3 Vincolo punto vendita

Ogni punto vendita può generare al massimo **1 vincita per settimana** (lunedì–domenica del concorso). Se il punto vendita ha già una vincita attiva nella settimana, le giocate provenienti da quel punto vendita non possono vincere, ma lo slot resta disponibile per giocatori di altri punti vendita.

### 3.4 Premi non assegnati

I premi non assegnati in una giornata **non vengono riassegnati** nei giorni successivi. Restano registrati nel sistema come slot non assegnati, visibili nel pannello di amministrazione per finalità di rendicontazione.

---

## 4. Garanzie di integrità e concorrenza

### 4.1 Lock pessimistico

L'istruzione `SELECT ... FOR UPDATE` (InnoDB row-level locking) garantisce che, in caso di giocate simultanee, un solo partecipante possa ottenere un determinato slot vincente. Le altre transazioni concorrenti attendono il rilascio del lock e procedono sugli slot rimanenti (o ricevono esito negativo se non ve ne sono).

### 4.2 Transazione atomica

L'intera operazione di assegnazione (aggiornamento dello slot + aggiornamento della giocata) avviene all'interno di una singola transazione database, rendendo impossibile uno stato parziale (slot assegnato senza giocata vincente o viceversa).

### 4.3 Non determinabilità

I momenti vincenti non sono determinabili a priori da nessun partecipante perché:

- Gli orari sono generati con un CSPRNG prima dell'inizio del concorso
- I dati degli slot vincenti non sono esposti ad alcuna interfaccia pubblica
- L'esito della giocata dipende dall'orario esatto di partecipazione e dalla disponibilità dello slot

---

## 5. Tracciabilità e audit

Ogni operazione viene registrata nei log dell'applicazione (canale `daily`):

- **Generazione slot:** data, distribuzione per premio, totali
- **Ogni tentativo di giocata:** ID giocata, ID utente, codice punto vendita, esito (vincita/non vincita), eventuale premio assegnato
- **Blocco punto vendita:** quando una giocata non può vincere per vincolo settimanale

I log sono conservati in file giornalieri nella directory `storage/logs/` dell'applicazione.

---

## 6. Riepilogo tecnico

| Proprietà | Valore |
|-----------|--------|
| Generatore casuale | `random_int()` — CSPRNG (PHP 8.3) |
| Lock concorrenza | `SELECT ... FOR UPDATE` (InnoDB) |
| Transazione | ACID con rollback automatico |
| Slot totali | 104 (pre-generati) |
| Periodo concorso | 20/04/2026 – 17/05/2026 (28 giorni) |
| Vincolo PV | Max 1 vincita/PV/settimana |
| Regola ore 12 | Assegnazione forzata dopo le 12:00 |
| Recovery premi | Non previsto (premi non assegnati restano tali) |
| Logging | Ogni operazione tracciata su file giornaliero |
