<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plays', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->after('is_winner');
        });

        DB::table('plays')->where('is_banned', true)->update(['status' => 'banned']);
        DB::table('plays')->where('is_banned', false)->update(['status' => 'validated']);

        Schema::table('plays', function (Blueprint $table) {
            $table->dropColumn('is_banned');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('plays', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->boolean('is_banned')->default(false)->after('is_winner');
        });

        DB::table('plays')->where('status', 'banned')->update(['is_banned' => true]);

        Schema::table('plays', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
