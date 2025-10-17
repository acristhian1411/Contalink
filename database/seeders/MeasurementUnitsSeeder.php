<?php

namespace Database\Seeders;

use App\Models\MeasurementUnit;
use Illuminate\Database\Seeder;

class MeasurementUnitsSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $defaultUnits = [
            [
                'unit_name' => 'Unidad',
                'unit_abbreviation' => 'u',
                'allows_decimals' => false,
                'is_active' => true
            ],
            [
                'unit_name' => 'Kilogramo',
                'unit_abbreviation' => 'kg',
                'allows_decimals' => true,
                'is_active' => true
            ],
            [
                'unit_name' => 'Gramo',
                'unit_abbreviation' => 'g',
                'allows_decimals' => true,
                'is_active' => true
            ],
            [
                'unit_name' => 'Litro',
                'unit_abbreviation' => 'L',
                'allows_decimals' => true,
                'is_active' => true
            ],
            [
                'unit_name' => 'Mililitro',
                'unit_abbreviation' => 'ml',
                'allows_decimals' => true,
                'is_active' => true
            ],
            [
                'unit_name' => 'Metro',
                'unit_abbreviation' => 'm',
                'allows_decimals' => true,
                'is_active' => true
            ],
            [
                'unit_name' => 'Centímetro',
                'unit_abbreviation' => 'cm',
                'allows_decimals' => true,
                'is_active' => true
            ],
            [
                'unit_name' => 'Caja',
                'unit_abbreviation' => 'caja',
                'allows_decimals' => false,
                'is_active' => true
            ],
            [
                'unit_name' => 'Paquete',
                'unit_abbreviation' => 'paq',
                'allows_decimals' => false,
                'is_active' => true
            ]
        ];

        foreach ($defaultUnits as $unit) {
            MeasurementUnit::firstOrCreate(
                ['unit_name' => $unit['unit_name']],
                [
                    'unit_abbreviation' => $unit['unit_abbreviation'],
                    'allows_decimals' => $unit['allows_decimals'],
                    'is_active' => $unit['is_active'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        }
    }
}