<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('final_draw_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('final_prize_id')->constrained('final_prizes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('play_id')->constrained('plays')->cascadeOnDelete();
            $table->enum('role', ['winner', 'substitute']);
            $table->unsignedTinyInteger('substitute_position')->nullable();
            $table->unsignedInteger('total_plays');
            $table->timestamp('drawn_at');
            $table->timestamps();

            $table->unique('user_id');
            $table->unique(['final_prize_id', 'role', 'substitute_position'], 'fdr_prize_role_position_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('final_draw_results');
    }
};
