# FASE 2 - Database: Migrations, Models, Seeders

**Priorità:** CRITICA
**Dipendenze:** Fase 1
**Stima effort:** Seconda fase

---

## 2.1 Migrations

### Tabella `users`
- [ ] id (bigIncrements)
- [ ] name (string 100)
- [ ] surname (string 100)
- [ ] birth_date (date)
- [ ] email (string 255, unique)
- [ ] phone (string 20)
- [ ] address (string 255)
- [ ] city (string 100)
- [ ] province (string 2)
- [ ] cap (string 5)
- [ ] password (string - bcrypt hash)
- [ ] privacy_consent (boolean, default false)
- [ ] marketing_consent (boolean, default false)
- [ ] newsletter_consent (boolean, default false) - consenso ricezione newsletter
- [ ] is_banned (boolean, default false)
- [ ] ban_reason (text, nullable)
- [ ] email_verified_at (timestamp, nullable)
- [ ] remember_token
- [ ] timestamps

### Tabella `stores` (Punti Vendita)
- [ ] id (bigIncrements)
- [ ] code (string 50, unique) - codice identificativo esposto nel punto vendita
- [ ] name (string 255) - ragione sociale
- [ ] address (string 255)
- [ ] city (string 100)
- [ ] province (string 2)
- [ ] cap (string 5)
- [ ] is_active (boolean, default true)
- [ ] timestamps

### Tabella `prizes` (Premi)
- [ ] id (bigIncrements)
- [ ] code (string 1, unique) - A, B, C, D, E
- [ ] name (string 255)
- [ ] description (text)
- [ ] value (decimal 8,2) - valore unitario IVA inclusa
- [ ] total_quantity (integer) - quantità totale nel concorso
- [ ] image (string 255, nullable) - path immagine premio
- [ ] timestamps

### Tabella `plays` (Giocate)
- [ ] id (bigIncrements)
- [ ] user_id (foreignId -> users)
- [ ] store_code (string 50) - codice punto vendita inserito dall'utente
- [ ] receipt_image (string 255) - path file scontrino
- [ ] played_at (datetime) - data/ora della giocata
- [ ] is_winner (boolean, default false)
- [ ] prize_id (foreignId -> prizes, nullable)
- [ ] winning_slot_id (foreignId -> winning_slots, nullable)
- [ ] is_banned (boolean, default false)
- [ ] ban_reason (text, nullable)
- [ ] banned_at (datetime, nullable)
- [ ] timestamps
- [ ] INDEX su (user_id, played_at) - per check 1 giocata/giorno
- [ ] INDEX su (store_code, is_winner, played_at) - per check 1 premio/PV/settimana

### Tabella `winning_slots` (Slot vincenti pre-generati)
- [ ] id (bigIncrements)
- [ ] prize_id (foreignId -> prizes)
- [ ] scheduled_date (date) - giorno assegnato
- [ ] scheduled_time (time) - ora casuale nel giorno
- [ ] is_assigned (boolean, default false)
- [ ] play_id (foreignId -> plays, nullable) - giocata che ha vinto questo slot
- [ ] assigned_at (datetime, nullable)
- [ ] timestamps
- [ ] INDEX su (scheduled_date, scheduled_time, is_assigned) - query principale instant win

### Tabella `admins` (Utenti backoffice)
- [ ] id (bigIncrements)
- [ ] name (string 255)
- [ ] email (string 255, unique)
- [ ] password (string)
- [ ] timestamps

### Tabella `sessions` (per session driver database)
- [ ] Usare migration standard Laravel `php artisan session:table`

---

## 2.2 Models con relazioni

### User
- [ ] hasMany(Play)
- [ ] Scope `banned()` / `active()`
- [ ] Attributo `plays_count` (withCount)
- [ ] Attributo `wins_count`
- [ ] Metodo `hasPlayedToday(): bool`
- [ ] Hidden: password, remember_token
- [ ] Casts: birth_date -> date, is_banned -> boolean, newsletter_consent -> boolean

### Store
- [ ] Scope `active()`
- [ ] Scope `byProvince($province)`
- [ ] Scope `byCity($city)`

### Prize
- [ ] hasMany(WinningSlot)
- [ ] hasMany(Play)
- [ ] Attributo `assigned_count` (quanti premi assegnati)
- [ ] Attributo `remaining_count` (quanti premi restano)
- [ ] Metodo `getScheduleDays(): array` - giorni della settimana in cui il premio è disponibile

### Play
- [ ] belongsTo(User)
- [ ] belongsTo(Prize, nullable)
- [ ] belongsTo(WinningSlot, nullable)
- [ ] Scope `winners()` / `losers()` / `banned()`
- [ ] Scope `forDate($date)`
- [ ] Casts: played_at -> datetime, is_winner -> boolean, is_banned -> boolean

### WinningSlot
- [ ] belongsTo(Prize)
- [ ] belongsTo(Play, nullable)
- [ ] Scope `available()` - non assegnati
- [ ] Scope `forDate($date)`
- [ ] Scope `pastTime($time)` - ora già passata
- [ ] Casts: scheduled_date -> date, is_assigned -> boolean

### Admin
- [ ] Modello separato per autenticazione Filament

---

## 2.3 Seeders

### PrizeSeeder
- [ ] Premio A: Caffè macinato Mokador Espresso Oro - €3,30 - qty 28
- [ ] Premio B: Caffè macinato Mokador Latta 100% Arabica - €4,60 - qty 28
- [ ] Premio C: Bicchierino a cuore Mokador - €5,00 - qty 20
- [ ] Premio D: T-shirt Mokador - €5,50 - qty 16
- [ ] Premio E: Grembiule a pettorina Mokador - €13,00 - qty 12

### AdminSeeder
- [ ] Creare admin di default (credenziali da `.env`)

### WinningSlotSeeder (o Command dedicato - vedi Fase 3)
- [ ] Generazione dei 104 slot vincenti distribuiti nei 28 giorni
- [ ] Questo è meglio gestirlo come Artisan Command (vedi Fase 3)

---

## Note

- Tutti i campi stringa che contengono nomi/indirizzi: `utf8mb4` per supporto caratteri speciali
- Gli indici composti sono fondamentali per le performance delle query instant win
- Il campo `store_code` in `plays` è una stringa (non FK) perché l'utente inserisce il codice manualmente
