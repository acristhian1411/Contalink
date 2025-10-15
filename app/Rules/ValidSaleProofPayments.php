<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidSaleProofPayments implements ValidationRule
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
        $totalVenta = collect($data['sale_details'] ?? [])
            ->sum(
                fn($detalle) =>
                ($detalle['sd_amount'] ?? 0) * ($detalle['sd_qty'] ?? 0)
            );

        // Calcular total de pagos
        $totalPagos = collect($value)
            ->sum(fn($pago) => intval($pago['amount']) ?? 0);

        // Validar que los pagos cubran el total
        if ($totalPagos < $totalVenta) {
            $fail('La suma de los tipos de pago no puede ser menor al total de la venta.');
        }
    }
}
