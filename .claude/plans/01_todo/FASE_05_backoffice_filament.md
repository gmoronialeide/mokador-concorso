# FASE 5 - Backoffice Filament

**Priorità:** ALTA
**Dipendenze:** Fase 1, Fase 2
**Stima effort:** Medio (Filament genera molto codice automaticamente)

---

## 5.1 Configurazione Panel

- [ ] Panel admin su `/admin`
- [ ] Guard `admin` separato
- [ ] Logo Mokador nel pannello
- [ ] Colori tema: primary arancione (#9D4A15), palette marrone
- [ ] Lingua italiana
- [ ] Navigation organizzata in gruppi:
  - Concorso (Dashboard, Giocate, Premi/Slot)
  - Anagrafiche (Utenti, Punti Vendita)
  - Sistema (Amministratori)

---

## 5.2 Dashboard (`app/Filament/Pages/Dashboard.php`)

### Widget statistiche
- [ ] **StatsOverview Widget** con:
  - Utenti registrati (totale + oggi)
  - Giocate totali (totale + oggi)
  - Premi assegnati / 104 totali
  - Giocate bannate
- [ ] **Chart Widget** - Giocate per giorno (line chart ultimi 28 giorni)
- [ ] **Chart Widget** - Registrazioni per giorno
- [ ] **Table Widget** - Ultime 10 giocate vincenti

---

## 5.3 UserResource (`app/Filament/Resources/UserResource.php`)

### Lista (Table)
- [ ] Colonne: ID, Nome, Cognome, Email, Telefono, Città, Registrato il, N. giocate, Newsletter, Bannato
- [ ] Filtri: bannato/non bannato, città, provincia, data registrazione
- [ ] Ricerca: nome, cognome, email, telefono
- [ ] Ordinamento: data registrazione, nome
- [ ] Bulk action: banna selezionati

### Dettaglio (Infolist/View)
- [ ] Tutti i dati utente
- [ ] Storico giocate (relation manager)
- [ ] Stato ban con motivazione

### Azioni
- [ ] **Banna utente**: modale con campo motivazione obbligatorio
- [ ] **Sbanna utente**: conferma
- [ ] **Esporta CSV**: elenco utenti filtrato

### Note
- [ ] NO creazione/modifica utenti da admin (si registrano solo dal frontend)

---

## 5.4 PlayResource (`app/Filament/Resources/PlayResource.php`)

### Lista (Table)
- [ ] Colonne: ID, Utente (nome+cognome), Punto Vendita, Data giocata, Vincente (badge sì/no), Premio, Bannata (badge)
- [ ] Filtri: vincente/non vincente, bannata/non bannata, punto vendita, data, premio
- [ ] Ricerca: nome utente, email utente, codice punto vendita
- [ ] Ordinamento: data giocata (default desc)

### Dettaglio (View)
- [ ] Tutti i dati della giocata
- [ ] **Preview scontrino**: immagine inline (da storage privato)
- [ ] Dati utente collegato
- [ ] Dati punto vendita
- [ ] Se vincente: dettaglio premio e slot assegnato

### Azioni
- [ ] **Banna giocata**: modale con campo motivazione obbligatorio
  - Se la giocata era vincente: liberare lo slot vincente (is_assigned = false, play_id = null) così il premio torna disponibile
  - Aggiornare play: is_banned = true, ban_reason, banned_at
  - Se la giocata era vincente: is_winner = false, prize_id = null
- [ ] **Sbanna giocata**: conferma (nota: il premio NON viene riassegnato automaticamente)
- [ ] **Esporta CSV**: elenco giocate filtrato

### Relation Manager su UserResource
- [ ] PlayRelationManager: mostra le giocate dell'utente nella pagina dettaglio utente

---

## 5.5 StoreResource (`app/Filament/Resources/StoreResource.php`)

### Lista (Table)
- [ ] Colonne: ID, Codice, Nome, Indirizzo, Città, Provincia, Attivo (toggle)
- [ ] Filtri: attivo/non attivo, provincia
- [ ] Ricerca: codice, nome, città
- [ ] Ordinamento: nome, provincia

### Form (Create/Edit)
- [ ] code: text, required, unique
- [ ] name: text, required
- [ ] address: text, required
- [ ] city: text, required
- [ ] province: select (elenco province italiane), required
- [ ] cap: text, required
- [ ] is_active: toggle, default true

### Azioni
- [ ] **Crea punto vendita**: form completo
- [ ] **Modifica punto vendita**: form completo
- [ ] **Disattiva/Attiva**: toggle rapido dalla lista
- [ ] **Import CSV**: azione custom per importazione massiva
  - Formato CSV: codice;nome;indirizzo;citta;provincia;cap
  - Validazione ogni riga
  - Report risultato (importati, errori, duplicati)
- [ ] **Esporta CSV**: elenco punti vendita

---

## 5.6 WinningSlotResource (`app/Filament/Resources/WinningSlotResource.php`)

### Lista (Table) - SOLA LETTURA
- [ ] Colonne: ID, Premio, Data programmata, Ora programmata, Assegnato (badge), Giocata vincente (link), Data assegnazione
- [ ] Filtri: assegnato/non assegnato, premio, data, settimana
- [ ] Ordinamento: data programmata (default ASC)
- [ ] Colorazione righe: verde = assegnato, bianco = disponibile, rosso = scaduto non assegnato

### Note
- [ ] Risorsa in sola lettura (no create/edit/delete da UI)
- [ ] Serve per monitoraggio: quanti premi assegnati, quanti mancano, distribuzione temporale

---

## 5.7 AdminResource (opzionale)

- [ ] CRUD semplice per gestire gli account admin del backoffice
- [ ] Solo se servono più admin, altrimenti basta il seeder

---

## Note generali

- Filament 5 genera automaticamente breadcrumbs, paginazione, notifiche toast
- Le azioni di ban devono loggare chi ha bannato e quando (audit trail)
- L'export CSV è nativo in Filament con il plugin `filament/actions`
- La preview scontrino richiede una rotta dedicata che serve il file da storage privato
