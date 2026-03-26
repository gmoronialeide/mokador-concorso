<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Genera i 104 slot vincenti richiamando il comando concorso:generate-slots.
 */
return new class extends Migration
{
    public function up(): void
    {
        Artisan::call('concorso:generate-slots');
    }

    public function down(): void
    {
        DB::table('winning_slots')->truncate();
    }
};
