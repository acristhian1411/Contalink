<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\Products;
use App\Models\Tills;

class ValidBusinessTransaction implements ValidationRule
{
    protected string $transactionType;
    protected array $context;

    public function __construct(string $transactionType, array $context = [])
    {
        $this->transactionType = $transactionType;
        $this->context = $context;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value) || empty($value)) {
            $fail('Los detalles de la transacción son requeridos.');
            return;
        }

        $data = request()->all();
        
        // Validate transaction details
        $this->validateTransactionDetails($value, $data, $fail);
        
        // Validate payment methods
        $this->validatePaymentMethods($data, $fail);
        
        // Validate business rules
        $this->validateBusinessRules($value, $data, $fail);
    }

    /**
     * Validate transaction details (products, quantities, amounts).
     */
    protected function validateTransactionDetails(array $details, array $data, Closure $fail): void
    {
        $totalAmount = 0;
        $productIds = [];

        foreach ($details as $index => $detail) {
            $productId = $detail['product_id'] ?? null;
            $quantity = $detail[$this->getQuantityField()] ?? 0;
            $amount = $detail[$this->getAmountField()] ?? 0;

            // Check for duplicate products
            if (in_array($productId, $productIds)) {
                $fail("El producto en la línea " . ($index + 1) . " está duplicado.");
                return;
            }
            $productIds[] = $productId;

            // Validate product exists and is active
            $product = Products::find($productId);
            if (!$product) {
                $fail("El producto en la línea " . ($index + 1) . " no existe.");
                return;
            }

            // Validate quantity based on measurement unit
            if (!$this->validateQuantityForProduct($product, $quantity)) {
                $fail("La cantidad en la línea " . ($index + 1) . " no es válida para la unidad de medida del producto.");
                return;
            }

            // Validate stock availability for sales
            if ($this->transactionType === 'sale' && $product->product_quantity < $quantity) {
                $fail("Stock insuficiente para el producto '{$product->product_name}'. Disponible: {$product->product_quantity}");
                return;
            }

            // Validate amount is reasonable
            if ($this->transactionType === 'sale') {
                $expectedAmount = $product->product_sale_price * $quantity;
                if (abs($amount - $expectedAmount) > ($expectedAmount * 0.1)) { // 10% tolerance
                    $fail("El monto en la línea " . ($index + 1) . " parece incorrecto.");
                    return;
                }
            }

            $totalAmount += $amount;
        }

        // Store total for payment validation
        $this->context['total_amount'] = $totalAmount;
    }

    /**
     * Validate payment methods cover the total amount.
     */
    protected function validatePaymentMethods(array $data, Closure $fail): void
    {
        $payments = $data['proofPayments'] ?? [];
        
        if (empty($payments)) {
            $fail('Debe incluir al menos un método de pago.');
            return;
        }

        $totalPayments = collect($payments)->sum('amount');
        $totalTransaction = $this->context['total_amount'] ?? 0;

        if ($totalPayments < $totalTransaction) {
            $fail('La suma de los pagos no puede ser menor al total de la transacción.');
            return;
        }

        // Allow overpayment but warn if excessive
        if ($totalPayments > ($totalTransaction * 1.5)) {
            $fail('El monto de los pagos parece excesivo comparado con el total.');
            return;
        }
    }

    /**
     * Validate business-specific rules.
     */
    protected function validateBusinessRules(array $details, array $data, Closure $fail): void
    {
        // Validate till has sufficient funds for purchases
        if ($this->transactionType === 'purchase') {
            $tillId = $data['till_id'] ?? null;
            $till = Tills::find($tillId);
            
            if ($till && $this->context['total_amount'] > $till->till_amount) {
                $fail('La caja no tiene fondos suficientes para esta compra.');
                return;
            }
        }

        // Validate transaction date is not in the future
        $transactionDate = $data[$this->transactionType . '_date'] ?? null;
        if ($transactionDate && strtotime($transactionDate) > time()) {
            $fail('La fecha de la transacción no puede ser futura.');
            return;
        }

        // Validate transaction number is unique
        $transactionNumber = $data[$this->transactionType . '_number'] ?? null;
        if ($transactionNumber) {
            $table = $this->transactionType === 'sale' ? 'sales' : 'purchases';
            $field = $this->transactionType . '_number';
            
            $exists = \DB::table($table)
                ->where($field, $transactionNumber)
                ->exists();
                
            if ($exists) {
                $fail('El número de ' . ($this->transactionType === 'sale' ? 'venta' : 'compra') . ' ya existe.');
                return;
            }
        }
    }

    /**
     * Validate quantity based on product's measurement unit.
     */
    protected function validateQuantityForProduct(Products $product, float $quantity): bool
    {
        if (!$product->measurementUnit) {
            return $quantity > 0;
        }

        $unitType = $product->measurementUnit->unit_type ?? 'decimal';
        
        switch ($unitType) {
            case 'integer':
                return is_int($quantity) && $quantity > 0;
            case 'decimal':
                return $quantity > 0;
            case 'weight':
                return $quantity >= 0.001; // Minimum 1 gram
            default:
                return $quantity > 0;
        }
    }

    /**
     * Get the quantity field name based on transaction type.
     */
    protected function getQuantityField(): string
    {
        return $this->transactionType === 'sale' ? 'sd_qty' : 'pd_qty';
    }

    /**
     * Get the amount field name based on transaction type.
     */
    protected function getAmountField(): string
    {
        return $this->transactionType === 'sale' ? 'sd_amount' : 'pd_amount';
    }
}