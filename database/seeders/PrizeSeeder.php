<?php

namespace Database\Seeders;

use App\Models\Prize;
use Illuminate\Database\Seeder;

class PrizeSeeder extends Seeder
{
    public function run(): void
    {
        $prizes = [
            [
                'code' => 'A',
                'name' => 'Caffè macinato Mokador Espresso Oro',
                'description' => 'Caffè macinato Mokador Espresso Oro in confezione da 250g',
                'value' => 3.30,
                'total_quantity' => 28,
            ],
            [
                'code' => 'B',
                'name' => 'Caffè macinato Mokador Latta 100% Arabica',
                'description' => 'Caffè macinato Mokador Latta 100% Arabica in confezione da 250g',
                'value' => 4.60,
                'total_quantity' => 28,
            ],
            [
                'code' => 'C',
                'name' => 'Bicchierino a cuore Mokador',
                'description' => 'Bicchierino a cuore Mokador in ceramica',
                'value' => 5.00,
                'total_quantity' => 20,
            ],
            [
                'code' => 'D',
                'name' => 'T-shirt Mokador',
                'description' => 'T-shirt Mokador in cotone con logo',
                'value' => 5.50,
                'total_quantity' => 16,
            ],
            [
                'code' => 'E',
                'name' => 'Grembiule a pettorina Mokador',
                'description' => 'Grembiule a pettorina Mokador in tessuto con logo',
                'value' => 13.00,
                'total_quantity' => 12,
            ],
        ];

        foreach ($prizes as $prize) {
            Prize::updateOrCreate(
                ['code' => $prize['code']],
                $prize
            );
        }
    }
}
