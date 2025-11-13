<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PaymentTypes;

class PaymentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PaymentTypes::firstOrCreate(['id' => 1], ['payment_type_desc' => 'Efectivo']);
        PaymentTypes::firstOrCreate(['id'=> 2], ['payment_type_desc'=> 'Tarjeta de crédito']);
        PaymentTypes::firstOrCreate(['id'=> 3], ['payment_type_desc'=> 'Tarjeta de débito']);
    }
}
