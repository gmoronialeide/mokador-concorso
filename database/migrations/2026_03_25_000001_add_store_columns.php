<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('sign_name', 255)->after('name');
            $table->string('vat_number', 20)->after('sign_name');
            $table->string('agent', 255)->nullable()->after('vat_number');
            $table->string('cap', 5)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['sign_name', 'vat_number', 'agent']);
            $table->string('cap', 5)->nullable(false)->change();
        });
    }
};
