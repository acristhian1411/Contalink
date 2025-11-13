<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ProofPayments;

class ProofPaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ProofPayments::firstOrCreate(['id' => 1], ['proof_payment_desc' => 'Ninguno', 'payment_type_id' => 1]);
        ProofPayments::firstOrCreate(['id'=> 2], ['proof_payment_desc' => 'Nro. Transacción', 'payment_type_id' => 2]);
        ProofPayments::firstOrCreate(['id'=> 3], ['proof_payment_desc' => 'Nro. Transacción', 'payment_type_id' => 3]);
    }
}
