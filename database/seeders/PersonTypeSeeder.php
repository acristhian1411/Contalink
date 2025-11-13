<?php

namespace Database\Seeders;

use App\Models\PersonTypes;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PersonTypeSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if(DB::table('person_types')->count() > 0){
            return;
        }
        PersonTypes::firstOrCreate(['id'=>0,'p_type_desc'=>'Funcionario','created_at'=> now(), 'updated_at'=> now()]);
        PersonTypes::firstOrCreate(['id'=>1,'p_type_desc'=>'Proveedor','created_at'=> now(), 'updated_at'=> now()]);
        PersonTypes::firstOrCreate(['id'=>2,'p_type_desc'=>'Cliente','created_at'=> now(), 'updated_at'=> now()]);
    }
}
