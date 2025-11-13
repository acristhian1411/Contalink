<?php

namespace Database\Seeders;

use App\Models\IvaType;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IvaTypeSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (IvaType::count() == 0) {
            IvaType::create([
                'id'=>0,
                'iva_type_percent'=>0,
                'iva_type_desc'=>'Exentas',
                'created_at'=> now(), 
                'updated_at'=> now()]); 
            IvaType::create([
                'id'=>1,
                'iva_type_percent'=>10,
                'iva_type_desc'=>'10%',
                'created_at'=> now(),
                'updated_at'=> now()
            ]);
            IvaType::create([
                'id'=>2,
                'iva_type_percent'=>5,
                'iva_type_desc'=>'5%',
                    'created_at'=> now(), 
                'updated_at'=> now()
            ]);
        }
        
    }
}
