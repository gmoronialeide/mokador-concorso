<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('store_code', 50);
            $table->string('receipt_image', 255);
            $table->datetime('played_at');
            $table->boolean('is_winner')->default(false);
            $table->foreignId('prize_id')->nullable()->constrained('prizes')->nullOnDelete();
            $table->unsignedBigInteger('winning_slot_id')->nullable();
            $table->boolean('is_banned')->default(false);
            $table->text('ban_reason')->nullable();
            $table->datetime('banned_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'played_at']);
            $table->index(['store_code', 'is_winner', 'played_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plays');
    }
};
