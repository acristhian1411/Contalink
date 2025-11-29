<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Products;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Products::firstOrCreate([
            'product_name'=>'Coca-Cola',
        'product_desc'=>'Gaseosa Coca-cola 1 lt',
        'product_image'=>null,
        'product_barcode'=>'12131',
        'product_cost_price'=>15000,
        'product_quantity'=>1,
        'product_selling_price'=>20000,
        'category_id'=>1,
        'iva_type_id'=>1,
        'brand_id'=>1,
        'measurement_unit_id'=>1,
        ]);
    }
}
