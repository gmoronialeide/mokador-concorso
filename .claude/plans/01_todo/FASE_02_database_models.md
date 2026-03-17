# FASE 2 - Database: Migrations, Models, Seeders

**Priorità:** CRITICA
**Dipendenze:** Fase 1
**Stima effort:** Seconda fase
**Stato:** ✅ COMPLETATA

---

## 2.1 Migrations

### Tabella `users`
- [x] id (bigIncrements)
- [x] name (string 100)
- [x] surname (string 100)
- [x] birth_date (date)
- [x] email (string 255, unique)
- [x] phone (string 20)
- [x] address (string 255)
- [x] city (string 100)
- [x] province (string 2)
- [x] cap (string 5)
- [x] password (string - bcrypt hash)
- [x] privacy_consent (boolean, default false)
- [x] marketing_consent (boolean, default false) — include anche consenso newsletter
- [x] is_banned (boolean, default false)
- [x] ban_reason (text, nullable)
- [x] email_verified_at (timestamp, nullable)
- [x] remember_token
- [x] timestamps

### Tabella `stores` (Punti Vendita)
- [x] id (bigIncrements)
- [x] code (string 50, unique) - codice identificativo esposto nel punto vendita
- [x] name (string 255) - ragione sociale
- [x] address (string 255)
- [x] city (string 100)
- [x] province (string 2)
- [x] cap (string 5)
- [x] is_active (boolean, default true)
- [x] timestamps

### Tabella `prizes` (Premi)
- [x] id (bigIncrements)
- [x] code (string 1, unique) - A, B, C, D, E
- [x] name (string 255)
- [x] description (text)
- [x] value (decimal 8,2) - valore unitario IVA inclusa
- [x] total_quantity (integer) - quantità totale nel concorso
- [x] image (string 255, nullable) - path immagine premio
- [x] timestamps

### Tabella `plays` (Giocate)
- [x] id (bigIncrements)
- [x] user_id (foreignId -> users)
- [x] store_code (string 50) - codice punto vendita inserito dall'utente
- [x] receipt_image (string 255) - path file scontrino
- [x] played_at (datetime) - data/ora della giocata
- [x] is_winner (boolean, default false)
- [x] prize_id (foreignId -> prizes, nullable)
- [x] winning_slot_id (foreignId -> winning_slots, nullable)
- [x] is_banned (boolean, default false)
- [x] ban_reason (text, nullable)
- [x] banned_at (datetime, nullable)
- [x] timestamps
- [x] INDEX su (user_id, played_at) - per check 1 giocata/giorno
- [x] INDEX su (store_code, is_winner, played_at) - per check 1 premio/PV/settimana

### Tabella `winning_slots` (Slot vincenti pre-generati)
- [x] id (bigIncrements)
- [x] prize_id (foreignId -> prizes)
- [x] scheduled_date (date) - giorno assegnato
- [x] scheduled_time (time) - ora casuale nel giorno
- [x] is_assigned (boolean, default false)
- [x] play_id (foreignId -> plays, nullable) - giocata che ha vinto questo slot
- [x] assigned_at (datetime, nullable)
- [x] timestamps
- [x] INDEX su (scheduled_date, scheduled_time, is_assigned) - query principale instant win

### Tabella `admins` (Utenti backoffice)
- [x] id (bigIncrements)
- [x] name (string 255)
- [x] email (string 255, unique)
- [x] password (string)
- [x] timestamps

### Tabella `sessions` (per session driver database)
- [x] Usare migration standard Laravel `php artisan session:table`

---

## 2.2 Models con relazioni

### User
- [x] hasMany(Play)
- [x] Scope `banned()` / `active()`
- [x] Attributo `plays_count` (accessor)
- [x] Attributo `wins_count` (accessor)
- [x] Metodo `hasPlayedToday(): bool`
- [x] Hidden: password, remember_token
- [x] Casts: birth_date -> date, is_banned -> boolean, marketing_consent -> boolean

### Store
- [x] Scope `active()`
- [x] Scope `byProvince($province)`
- [x] Scope `byCity($city)`

### Prize
- [x] hasMany(WinningSlot)
- [x] hasMany(Play)
- [x] Attributo `assigned_count` (quanti premi assegnati)
- [x] Attributo `remaining_count` (quanti premi restano)
- [x] Metodo `getScheduleDays(): array` - restituisce le date distinte degli slot assegnati al premio

### Play
- [x] belongsTo(User)
- [x] belongsTo(Prize, nullable)
- [x] belongsTo(WinningSlot, nullable)
- [x] Scope `winners()` / `losers()` / `banned()`
- [x] Scope `forDate($date)`
- [x] Casts: played_at -> datetime, is_winner -> boolean, is_banned -> boolean

### WinningSlot
- [x] belongsTo(Prize)
- [x] belongsTo(Play, nullable)
- [x] Scope `available()` - non assegnati
- [x] Scope `forDate($date)`
- [x] Scope `pastTime($time)` - ora già passata
- [x] Casts: scheduled_date -> date, is_assigned -> boolean

### Admin
- [x] Modello separato per autenticazione Filament

---

## 2.3 Seeders

### PrizeSeeder
- [x] Premio A: Caffè macinato Mokador Espresso Oro - €3,30 - qty 28
- [x] Premio B: Caffè macinato Mokador Latta 100% Arabica - €4,60 - qty 28
- [x] Premio C: Bicchierino a cuore Mokador - €5,00 - qty 20
- [x] Premio D: T-shirt Mokador - €5,50 - qty 16
- [x] Premio E: Grembiule a pettorina Mokador - €13,00 - qty 12

### AdminSeeder
- [x] Creare admin di default (credenziali da `.env`)

### WinningSlotSeeder (o Command dedicato - vedi Fase 3)
- [ ] Generazione dei 104 slot vincenti distribuiti nei 28 giorni
- [ ] Questo è meglio gestirlo come Artisan Command (vedi Fase 3)

---

## Note

- Tutti i campi stringa che contengono nomi/indirizzi: `utf8mb4` per supporto caratteri speciali
- Gli indici composti sono fondamentali per le performance delle query instant win
- Il campo `store_code` in `plays` è una stringa (non FK) perché l'utente inserisce il codice manualmente
- Dipendenza circolare plays↔winning_slots risolta: plays creata prima con winning_slot_id come unsignedBigInteger, FK aggiunta nella migration winning_slots
