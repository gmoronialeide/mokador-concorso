<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plays', function (Blueprint $table): void {
            $table->string('verification_type', 10)->nullable()->after('notes');
            $table->json('ocr_raw')->nullable()->after('verification_type');
            $table->index(['status', 'verification_type'], 'plays_status_verification_type_idx');
        });
    }

    public function down(): void
    {
        Schema::table('plays', function (Blueprint $table): void {
            $table->dropIndex('plays_status_verification_type_idx');
            $table->dropColumn(['verification_type', 'ocr_raw']);
        });
    }
};
