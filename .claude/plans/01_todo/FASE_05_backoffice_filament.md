# FASE 5 - Backoffice Filament

**Priorità:** ALTA
**Dipendenze:** Fase 1, Fase 2
**Stima effort:** Medio (Filament genera molto codice automaticamente)
**Stato:** ✅ COMPLETATA

---

## 5.1 Configurazione Panel

- [x] Panel admin su `/admin`
- [x] Guard `admin` separato
- [x] Colori tema: primary arancione (#9D4A15), palette marrone
- [x] Navigation organizzata in gruppi: Concorso, Anagrafiche, Sistema

---

## 5.2 Dashboard

### Widget statistiche
- [x] **PrizeStatsOverview** — utenti registrati (totale+oggi), giocate (totale+oggi), premi assegnati/104, scaduti, bannate
- [x] **UnassignedPrizesWidget** — tabella premi non assegnati per giorno (scaduti)
- [x] **PlaysChartWidget** — line chart giocate/vincite per giorno
- [x] **LatestWinsWidget** — ultime 10 vincite

---

## 5.3 UserResource

### Lista (Table)
- [x] Colonne: ID, Cognome, Nome, Email, Telefono, Città, Registrato il, N. giocate, Marketing, Bannato
- [x] Filtri: bannato/non bannato, provincia
- [x] Ricerca: nome, cognome, email, telefono
- [x] Ordinamento: data registrazione (default desc), cognome
- [x] Bulk action: banna selezionati (con motivazione)

### Dettaglio (Infolist/View)
- [x] Tutti i dati utente (personali, indirizzo, stato)
- [x] Storico giocate (PlaysRelationManager)
- [x] Stato ban con motivazione

### Azioni
- [x] **Banna utente**: modale con campo motivazione obbligatorio
- [x] **Sbanna utente**: conferma

### Note
- [x] NO creazione/modifica utenti da admin

---

## 5.4 PlayResource

### Lista (Table)
- [x] Colonne: ID, Utente (nome+cognome), Punto Vendita, Data giocata, Vincente, Premio, Bannata
- [x] Filtri: vincente/non vincente, bannata/non bannata, premio
- [x] Ricerca: nome utente, codice punto vendita
- [x] Ordinamento: data giocata (default desc)

### Dettaglio (View)
- [x] Tutti i dati della giocata
- [x] Preview scontrino (ImageEntry da disk receipts)
- [x] Dati utente collegato
- [x] Se vincente: dettaglio premio e slot assegnato
- [x] Se bannata: motivazione e data ban

### Azioni
- [x] **Banna giocata**: modale con motivazione — libera lo slot vincente se vincente
- [x] **Sbanna giocata**: conferma (premio NON riassegnato)

### Relation Manager su UserResource
- [x] PlaysRelationManager: giocate dell'utente nella pagina dettaglio

---

## 5.5 StoreResource

### Lista (Table)
- [x] Colonne: ID, Codice, Nome, Indirizzo, Città, Provincia, Attivo
- [x] Filtri: attivo/non attivo, provincia
- [x] Ricerca: codice, nome, città
- [x] Ordinamento: nome (default)

### Form (Create/Edit)
- [x] code, name, address, city, province, cap, is_active (toggle)

### Azioni
- [x] Crea/Modifica/Elimina punti vendita
- [ ] Import CSV (azione custom per importazione massiva) — da implementare se necessario
- [ ] Esporta CSV

---

## 5.6 WinningSlotResource (sola lettura)

### Lista (Table)
- [x] Colonne: ID, Premio (badge colorato), Nome premio, Data/Ora programmata, Stato (badge: Assegnato/Disponibile/Non assegnato), Vincitore, Data assegnazione
- [x] Filtri: assegnato/non assegnato, premio
- [x] Ordinamento: data programmata ASC (default)
- [x] Colorazione badge: verde=assegnato, grigio=disponibile, rosso=scaduto non assegnato
- [x] Risorsa in sola lettura (no create/edit/delete)

---

## Note implementative

- Filament 5: `Schema` al posto di `Form`/`Infolist`, `$navigationIcon` tipo `string|BackedEnum|null`, `$navigationGroup` tipo `string|UnitEnum|null`, `$heading` non-static su `ChartWidget`
- Components layout (`Section`) in `Filament\Schemas\Components\`, fields in `Filament\Forms\Components\`, entries in `Filament\Infolists\Components\`
