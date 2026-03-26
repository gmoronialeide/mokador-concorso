<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Inserisce i dati di produzione che devono esistere in ogni ambiente:
 * - Premi settimanali (instant win) A-E
 * - Premi finali (estrazione finale)
 * - Admin di default
 *
 * I punti vendita verranno aggiunti in una migrazione separata
 * quando saranno disponibili i dati.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // --- Premi settimanali (instant win) ---
        DB::table('prizes')->insert([
            [
                'code' => 'A',
                'name' => 'Caffè macinato Mokador Espresso Oro',
                'description' => 'Caffè macinato Mokador Espresso Oro in confezione da 250g',
                'value' => 3.30,
                'total_quantity' => 28,
                'image' => 'macinato-oro.png',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'B',
                'name' => 'Caffè macinato Mokador Latta 100% Arabica',
                'description' => 'Caffè macinato Mokador Latta 100% Arabica in confezione da 250g',
                'value' => 4.60,
                'total_quantity' => 28,
                'image' => 'latta-arabica.png',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'C',
                'name' => 'Bicchierino a cuore Mokador',
                'description' => 'Bicchierino a cuore Mokador in ceramica',
                'value' => 5.00,
                'total_quantity' => 20,
                'image' => 'bicchierini.png',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'D',
                'name' => 'T-shirt Mokador',
                'description' => 'T-shirt Mokador in cotone con logo',
                'value' => 5.50,
                'total_quantity' => 16,
                'image' => 'maglietta.png',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'E',
                'name' => 'Grembiule a pettorina Mokador',
                'description' => 'Grembiule a pettorina Mokador in tessuto con logo',
                'value' => 13.00,
                'total_quantity' => 12,
                'image' => 'grembiuli.png',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // --- Premi finali (estrazione finale) ---
        DB::table('final_prizes')->insert([
            [
                'name' => 'Premio Finale 1° — Soggiorno Vacanza',
                'description' => 'Soggiorno vacanza per due persone (da definire)',
                'value' => 0.00,
                'position' => 1,
                'drawn_at' => null,
                'drawn_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Premio Finale 2° — Weekend Benessere',
                'description' => 'Weekend benessere per due persone (da definire)',
                'value' => 0.00,
                'position' => 2,
                'drawn_at' => null,
                'drawn_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Premio Finale 3° — Cesto Mokador Premium',
                'description' => 'Cesto regalo Mokador Premium (da definire)',
                'value' => 0.00,
                'position' => 3,
                'drawn_at' => null,
                'drawn_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // --- Admin di default ---
        DB::table('admins')->insert([
            'name' => config('app.admin_name', 'Admin Mokador'),
            'email' => config('app.admin_email', 'admin@mokador.it'),
            'password' => Hash::make(config('app.admin_password', 'changeme123')),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('prizes')->whereIn('code', ['A', 'B', 'C', 'D', 'E'])->delete();
        DB::table('final_prizes')->whereIn('position', [1, 2, 3])->delete();
        DB::table('admins')->where('email', config('app.admin_email', 'admin@mokador.it'))->delete();
    }
};
