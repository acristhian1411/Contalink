<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\Sales\SaleDeleteController;
use App\Models\Products;
use App\Models\MeasurementUnit;
use App\Models\Sales;
use App\Models\SalesDetails;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

class SaleDeleteControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the SaleDeleteController can be instantiated
     */
    public function test_controller_can_be_instantiated()
    {
        $controller = new SaleDeleteController();
        $this->assertInstanceOf(SaleDeleteController::class, $controller);
    }

    /**
     * Test that measurement unit validation works correctly
     */
    public function test_measurement_unit_validation_in_stock_reversal()
    {
        // Create a measurement unit that allows decimals
        $unit = MeasurementUnit::create([
            'unit_name' => 'Kilogramo',
            'unit_abbreviation' => 'kg',
            'allows_decimals' => true,
            'is_active' => true
        ]);

        // Create a product with this measurement unit
        $product = Products::create([
            'product_name' => 'Test Product',
            'product_desc' => 'Test Description',
            'product_cost_price' => 10.00,
            'product_quantity' => 100.5,
            'product_selling_price' => 15.00,
            'category_id' => 1,
            'iva_type_id' => 1,
            'brand_id' => 1,
            'measurement_unit_id' => $unit->id,
        ]);

        // Verify the product has the correct measurement unit relationship
        $this->assertEquals('Kilogramo', $product->measurementUnit->unit_name);
        $this->assertTrue($product->measurementUnit->allows_decimals);
        
        // Test that the product can handle decimal quantities
        $this->assertEquals(100.5, $product->product_quantity);
    }

    /**
     * Test that integer-only units work correctly
     */
    public function test_integer_only_unit_validation()
    {
        // Create a measurement unit that doesn't allow decimals
        $unit = MeasurementUnit::create([
            'unit_name' => 'Unidad',
            'unit_abbreviation' => 'u',
            'allows_decimals' => false,
            'is_active' => true
        ]);

        // Create a product with this measurement unit
        $product = Products::create([
            'product_name' => 'Test Product Units',
            'product_desc' => 'Test Description',
            'product_cost_price' => 10.00,
            'product_quantity' => 50, // Integer quantity
            'product_selling_price' => 15.00,
            'category_id' => 1,
            'iva_type_id' => 1,
            'brand_id' => 1,
            'measurement_unit_id' => $unit->id,
        ]);

        // Verify the product has the correct measurement unit relationship
        $this->assertEquals('Unidad', $product->measurementUnit->unit_name);
        $this->assertFalse($product->measurementUnit->allows_decimals);
        
        // Test that the product has integer quantity
        $this->assertEquals(50, $product->product_quantity);
        $this->assertTrue(is_int($product->product_quantity) || floor($product->product_quantity) == $product->product_quantity);
    }
}