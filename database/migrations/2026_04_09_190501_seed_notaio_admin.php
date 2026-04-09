<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('admins')->insert([
            'name' => config('app.notaio_name', 'Notaio'),
            'email' => config('app.notaio_email', 'notaio@mokador.it'),
            'password' => Hash::make(config('app.notaio_password', 'changeme123')),
            'role' => 'notaio',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('admins')->where('email', config('app.notaio_email', 'notaio@mokador.it'))->delete();
    }
};
