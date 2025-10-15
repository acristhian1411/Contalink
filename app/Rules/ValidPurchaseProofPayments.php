<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidPurchaseProofPayments implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value) || empty($value)) {
            $fail('Debe seleccionar al menos un tipo de pago.');
            return;
        }

        $data = request()->all();

        // Calcular total de la venta (sumar precio * cantidad de cada detalle)
        $totalCompra = collect($data['purchase_details'] ?? [])
            ->sum(
                fn($detalle) =>
                ($detalle['pd_amount'] ?? 0) * ($detalle['pd_qty'] ?? 0)
            );

        // Calcular total de pagos
        $totalPagos = collect($value)
            ->sum(fn($pago) => intval($pago['amount']) ?? 0);

        // Validar que los pagos cubran el total
        if ($totalPagos < $totalCompra) {
            $fail('La suma de los tipos de pago no puede ser menor al total de la venta.');
        }
    }
}
