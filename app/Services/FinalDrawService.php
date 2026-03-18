<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\FinalDrawResult;
use App\Models\FinalPrize;
use App\Models\Play;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinalDrawService
{
    /**
     * Recupera gli utenti eleggibili con il conteggio giocate valide (peso).
     *
     * Eleggibili: utenti non bannati, con almeno 1 giocata non bannata,
     * non già estratti. Anche vincitori instant win sono inclusi.
     *
     * @return Collection<int, object{user_id: int, eligible_plays_count: int}>
     */
    public function getEligibleUsers(): Collection
    {
        $alreadyDrawnUserIds = FinalDrawResult::pluck('user_id');

        return User::query()
            ->where('is_banned', false)
            ->whereNotIn('id', $alreadyDrawnUserIds)
            ->whereHas('plays', fn ($q) => $q->where('is_banned', false))
            ->withCount(['plays as eligible_plays_count' => fn ($q) => $q->where('is_banned', false)])
            ->get()
            ->map(fn (User $user) => (object) [
                'user_id' => $user->id,
                'eligible_plays_count' => (int) $user->eligible_plays_count,
            ]);
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

            $eligible = $this->getEligibleUsers();
            $requiredCount = $prizes->count() + ($prizes->count() * 3);

            if ($eligible->count() < $requiredCount) {
                throw new \RuntimeException(
                    "Servono almeno {$requiredCount} utenti eleggibili, ne sono disponibili {$eligible->count()}."
                );
            }

            $pool = $eligible->keyBy('user_id');
            $now = Carbon::now();
            $results = [];

            foreach ($prizes as $prize) {
                $drawn = $this->weightedRandom($pool);
                $pool->forget($drawn->user_id);

                $result = FinalDrawResult::create([
                    'final_prize_id' => $prize->id,
                    'user_id' => $drawn->user_id,
                    'role' => 'winner',
                    'substitute_position' => null,
                    'total_plays' => $drawn->eligible_plays_count,
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
                    'user_id' => $drawn->user_id,
                    'total_plays' => $drawn->eligible_plays_count,
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

            $eligible = $this->getEligibleUsers();
            $requiredSubstitutes = $prizes->count() * 3;

            if ($eligible->count() < $requiredSubstitutes) {
                throw new \RuntimeException(
                    "Servono almeno {$requiredSubstitutes} utenti eleggibili per i sostituti, ne sono disponibili {$eligible->count()}."
                );
            }

            $pool = $eligible->keyBy('user_id');
            $now = Carbon::now();
            $results = [];

            foreach ($prizes as $prize) {
                for ($pos = 1; $pos <= 3; $pos++) {
                    $drawn = $this->weightedRandom($pool);
                    $pool->forget($drawn->user_id);

                    $result = FinalDrawResult::create([
                        'final_prize_id' => $prize->id,
                        'user_id' => $drawn->user_id,
                        'role' => 'substitute',
                        'substitute_position' => $pos,
                        'total_plays' => $drawn->eligible_plays_count,
                        'drawn_at' => $now,
                    ]);

                    $results[] = $result;

                    Log::channel('daily')->info('Estrazione finale: SOSTITUTO', [
                        'prize_id' => $prize->id,
                        'prize_name' => $prize->name,
                        'prize_position' => $prize->position,
                        'substitute_position' => $pos,
                        'user_id' => $drawn->user_id,
                        'total_plays' => $drawn->eligible_plays_count,
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

    /**
     * Estrazione pesata: seleziona un utente casualmente con probabilità
     * proporzionale al numero di giocate valide.
     *
     * Usa random_int() (CSPRNG) per garanzia crittografica.
     *
     * @param Collection<int, object{user_id: int, eligible_plays_count: int}> $pool
     */
    private function weightedRandom(Collection $pool): object
    {
        if ($pool->isEmpty()) {
            throw new \RuntimeException('Pool di estrazione vuoto.');
        }

        $totalWeight = $pool->sum('eligible_plays_count');
        $random = random_int(1, $totalWeight);

        $cumulative = 0;
        foreach ($pool as $entry) {
            $cumulative += $entry->eligible_plays_count;
            if ($cumulative >= $random) {
                return $entry;
            }
        }

        return $pool->last();
    }
}
