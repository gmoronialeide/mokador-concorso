<?php

namespace App\Services;

use App\Models\Play;
use App\Models\Prize;
use App\Models\WinningSlot;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InstantWinService
{
    /**
     * Tenta l'instant win per una giocata.
     *
     * Logica:
     * 1. Verifica vincolo punto vendita (max 1 premio/PV/settimana)
     * 2. Cerca slot vincenti disponibili per oggi
     *    - Modalità normale: scheduled_time <= ora corrente
     *    - Regola ore 12: dopo le 12:00 tutti gli slot non assegnati del giorno
     * 3. Assegna lo slot al giocatore (con lock pessimistico)
     */
    public function attempt(Play $play): ?Prize
    {
        $now = Carbon::now();
        $today = $now->toDateString();
        $currentTime = $now->format('H:i:s');

        // Vincolo punto vendita: max 1 premio per PV per settimana
        if ($this->hasStoreWonThisWeek($play->store_code, $today)) {
            Log::channel('daily')->info('InstantWin: PV già vincitore questa settimana', [
                'play_id' => $play->id,
                'store_code' => $play->store_code,
                'user_id' => $play->user_id,
            ]);
            return null;
        }

        $prize = DB::transaction(function () use ($play, $today, $currentTime, $now) {
            $afterNoon = $currentTime >= '12:00:00';

            // Query base: slot del giorno, non assegnati, con lock
            $query = WinningSlot::whereDate('scheduled_date', $today)
                ->where('is_assigned', false)
                ->lockForUpdate()
                ->orderBy('scheduled_time', 'asc');

            if (! $afterNoon) {
                // Modalità normale: solo slot con ora già passata
                $query->where('scheduled_time', '<=', $currentTime);
            }
            // Dopo le 12:00: tutti gli slot non assegnati del giorno (regola ore 12)

            $slot = $query->first();

            if (! $slot) {
                return null;
            }

            // Assegna lo slot
            $slot->update([
                'is_assigned' => true,
                'play_id' => $play->id,
                'assigned_at' => $now,
            ]);

            $play->update([
                'is_winner' => true,
                'prize_id' => $slot->prize_id,
                'winning_slot_id' => $slot->id,
            ]);

            return $slot->prize;
        });

        Log::channel('daily')->info($prize ? 'InstantWin: VINCITA' : 'InstantWin: nessuna vincita', [
            'play_id' => $play->id,
            'user_id' => $play->user_id,
            'store_code' => $play->store_code,
            'prize' => $prize?->code,
        ]);

        return $prize;
    }

    /**
     * Restituisce [lunedì, domenica] della settimana del concorso per una data.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function getWeekBounds(string $date): array
    {
        $carbon = Carbon::parse($date);
        $monday = $carbon->copy()->startOfWeek(Carbon::MONDAY);
        $sunday = $carbon->copy()->endOfWeek(Carbon::SUNDAY);

        return [$monday, $sunday];
    }

    /**
     * Verifica se un punto vendita ha già vinto nella settimana della data specificata.
     */
    /**
     * Verifica se un punto vendita ha già vinto nella settimana.
     * Include anche vincite bannate: un PV bannato NON torna in gioco.
     */
    public function hasStoreWonThisWeek(string $storeCode, string $date): bool
    {
        [$weekStart, $weekEnd] = $this->getWeekBounds($date);

        return Play::where('store_code', $storeCode)
            ->where('is_winner', true)
            ->whereBetween('played_at', [
                $weekStart->startOfDay(),
                $weekEnd->endOfDay(),
            ])
            ->exists();
    }
}
