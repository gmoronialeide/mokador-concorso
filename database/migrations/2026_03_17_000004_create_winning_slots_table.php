<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('winning_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prize_id')->constrained('prizes')->cascadeOnDelete();
            $table->date('scheduled_date');
            $table->time('scheduled_time');
            $table->boolean('is_assigned')->default(false);
            $table->foreignId('play_id')->nullable()->constrained('plays')->nullOnDelete();
            $table->datetime('assigned_at')->nullable();
            $table->timestamps();

            $table->index(['scheduled_date', 'scheduled_time', 'is_assigned'], 'winning_slots_lookup_index');
        });

        // Add FK from plays.winning_slot_id to winning_slots
        Schema::table('plays', function (Blueprint $table) {
            $table->foreign('winning_slot_id')->references('id')->on('winning_slots')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('plays', function (Blueprint $table) {
            $table->dropForeign(['winning_slot_id']);
        });
        Schema::dropIfExists('winning_slots');
    }
};
