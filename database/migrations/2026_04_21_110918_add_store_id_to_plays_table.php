<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plays', function (Blueprint $table) {
            $table->foreignId('store_id')
                ->nullable()
                ->after('user_id')
                ->constrained('stores')
                ->nullOnDelete();

            $table->index(['store_id', 'played_at']);
        });

        Artisan::call('plays:backfill-store-id');
    }

    public function down(): void
    {
        Schema::table('plays', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
            $table->dropIndex(['store_id', 'played_at']);
            $table->dropColumn('store_id');
        });
    }
};
