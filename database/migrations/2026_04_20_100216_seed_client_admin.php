<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('admins')->insert([
            'name' => 'Mokador',
            'email' => 'concorso@mokador.it',
            'password' => Hash::make('Mok4dorC0nc0rsO?'),
            'role' => 'admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('admins')->where('email', 'concorso@mokador.it')->delete();
    }
};
