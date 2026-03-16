<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Admin::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@mokador.it')],
            [
                'name' => env('ADMIN_NAME', 'Admin Mokador'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'changeme123')),
            ]
        );
    }
}
