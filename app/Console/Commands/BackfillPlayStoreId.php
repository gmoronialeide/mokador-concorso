<?php

namespace App\Console\Commands;

use App\Models\Store;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillPlayStoreId extends Command
{
    protected $signature = 'plays:backfill-store-id';

    protected $description = 'Backfilla plays.store_id dai punti vendita esistenti (idempotente).';

    public function handle(): int
    {
        $codes = DB::table('plays')
            ->whereNull('store_id')
            ->whereNotNull('store_code')
            ->distinct()
            ->pluck('store_code');

        foreach ($codes as $code) {
            $stores = Store::where('code', $code)->orderBy('id')->get();
            $storeId = $stores->count() === 1 ? $stores->first()->id : null;

            if ($storeId === null) {
                $this->line("[skip] {$code}: nessun store risolvibile");

                continue;
            }

            $updated = DB::table('plays')
                ->where('store_code', $code)
                ->whereNull('store_id')
                ->update(['store_id' => $storeId]);

            $this->line("[ok] {$code} -> store_id={$storeId} ({$updated} plays)");
        }

        return self::SUCCESS;
    }
}
