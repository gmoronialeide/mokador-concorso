<?php

namespace App\Services;

use App\Enums\PlayStatus;
use App\Models\Admin;
use App\Models\FinalDrawResult;
use App\Models\FinalPrize;
use App\Models\Play;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinalDrawService
{
    /**
     * Recupera le giocate eleggibili per l'estrazione finale.
     *
     * Eleggibili: giocate non bannate, di utenti non bannati,
     * il cui utente non è già stato estratto. Ogni giocata = 1 biglietto equiprobabile.
     *
     * @return Collection<int, Play>
     */
    public function getEligiblePlays(): Collection
    {
        $alreadyDrawnUserIds = FinalDrawResult::pluck('user_id');

        return Play::query()
            ->whereNot('status', PlayStatus::Banned)
            ->whereHas('user', fn ($q) => $q->where('is_banned', false))
            ->whereNotIn('user_id', $alreadyDrawnUserIds)
            ->get();
    }

    /**
     * Fase 1: Estrae i 3 vincitori dei 3 premi finali.
     *
     * @return array<FinalDrawResult>
     */
    public function drawWinners(Admin $admin): array
    {
        return DB::transaction(function () use ($admin) {
            $prizes = FinalPrize::orderBy('position')->lockForUpdate()->get();

            if ($prizes->isEmpty()) {
                throw new \RuntimeException('Nessun premio finale configurato.');
            }

            if ($prizes->where('drawn_at', '!=', null)->isNotEmpty()) {
                throw new \RuntimeException('I vincitori sono già stati estratti.');
            }

            $pool = $this->getEligiblePlays();
            $uniqueUsers = $pool->unique('user_id')->count();
            $requiredCount = $prizes->count() + ($prizes->count() * 3);

            if ($uniqueUsers < $requiredCount) {
                throw new \RuntimeException(
                    "Servono almeno {$requiredCount} utenti eleggibili, ne sono disponibili {$uniqueUsers}."
                );
            }

            $now = Carbon::now();
            $results = [];

            foreach ($prizes as $prize) {
                $drawn = $pool->random();
                $userId = $drawn->user_id;

                $totalPlays = $pool->where('user_id', $userId)->count();

                $pool = $pool->where('user_id', '!=', $userId)->values();

                $result = FinalDrawResult::create([
                    'final_prize_id' => $prize->id,
                    'user_id' => $userId,
                    'play_id' => $drawn->id,
                    'role' => 'winner',
                    'substitute_position' => null,
                    'total_plays' => $totalPlays,
                    'drawn_at' => $now,
                ]);

                $prize->update([
                    'drawn_at' => $now,
                    'drawn_by' => $admin->id,
                ]);

                $results[] = $result;

                Log::channel('daily')->info('Estrazione finale: VINCITORE', [
                    'prize_id' => $prize->id,
                    'prize_name' => $prize->name,
                    'prize_position' => $prize->position,
                    'user_id' => $userId,
                    'play_id' => $drawn->id,
                    'total_plays' => $totalPlays,
                    'admin_id' => $admin->id,
                ]);
            }

            return $results;
        });
    }

    /**
     * Fase 2: Estrae 3 sostituti per ciascun premio finale.
     *
     * @return array<FinalDrawResult>
     */
    public function drawSubstitutes(Admin $admin): array
    {
        return DB::transaction(function () use ($admin) {
            $prizes = FinalPrize::orderBy('position')->lockForUpdate()->get();

            if ($prizes->where('drawn_at', null)->isNotEmpty()) {
                throw new \RuntimeException('Bisogna prima estrarre tutti i vincitori.');
            }

            $existingSubstitutes = FinalDrawResult::where('role', 'substitute')->exists();
            if ($existingSubstitutes) {
                throw new \RuntimeException('I sostituti sono già stati estratti.');
            }

            $pool = $this->getEligiblePlays();
            $uniqueUsers = $pool->unique('user_id')->count();
            $requiredSubstitutes = $prizes->count() * 3;

            if ($uniqueUsers < $requiredSubstitutes) {
                throw new \RuntimeException(
                    "Servono almeno {$requiredSubstitutes} utenti eleggibili per i sostituti, ne sono disponibili {$uniqueUsers}."
                );
            }

            $now = Carbon::now();
            $results = [];

            foreach ($prizes as $prize) {
                for ($pos = 1; $pos <= 3; $pos++) {
                    $drawn = $pool->random();
                    $userId = $drawn->user_id;

                    $totalPlays = $pool->where('user_id', $userId)->count();

                    $pool = $pool->where('user_id', '!=', $userId)->values();

                    $result = FinalDrawResult::create([
                        'final_prize_id' => $prize->id,
                        'user_id' => $userId,
                        'play_id' => $drawn->id,
                        'role' => 'substitute',
                        'substitute_position' => $pos,
                        'total_plays' => $totalPlays,
                        'drawn_at' => $now,
                    ]);

                    $results[] = $result;

                    Log::channel('daily')->info('Estrazione finale: SOSTITUTO', [
                        'prize_id' => $prize->id,
                        'prize_name' => $prize->name,
                        'prize_position' => $prize->position,
                        'substitute_position' => $pos,
                        'user_id' => $userId,
                        'play_id' => $drawn->id,
                        'total_plays' => $totalPlays,
                        'admin_id' => $admin->id,
                    ]);
                }
            }

            return $results;
        });
    }

    /**
     * Annulla l'estrazione dei sostituti.
     */
    public function resetSubstitutes(): void
    {
        DB::transaction(function () {
            FinalDrawResult::where('role', 'substitute')->delete();

            Log::channel('daily')->info('Estrazione finale: sostituti annullati');
        });
    }

    /**
     * Annulla l'intera estrazione (vincitori + sostituti).
     */
    public function resetAll(): void
    {
        DB::transaction(function () {
            FinalDrawResult::query()->delete();

            FinalPrize::query()->update([
                'drawn_at' => null,
                'drawn_by' => null,
            ]);

            Log::channel('daily')->info('Estrazione finale: TUTTO annullato');
        });
    }
}
