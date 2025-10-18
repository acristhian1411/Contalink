<?php

namespace App\Validators;

use App\Models\MeasurementUnit;
use App\Utils\QuantityValidationException;

class QuantityValidator
{
    /**
     * Validate quantity based on measurement unit rules
     *
     * @param mixed $quantity
     * @param MeasurementUnit $unit
     * @return bool
     */
    public static function validate($quantity, MeasurementUnit $unit): bool
    {
        // Validar que sea numérico y positivo
        if (!is_numeric($quantity) || $quantity <= 0) {
            throw new QuantityValidationException("Cantidad no valida");
        }

        // Si la unidad no permite decimales, validar que sea entero
        if (!$unit->allows_decimals && floor($quantity) != $quantity) {
            throw new QuantityValidationException("Este producto no permite decimales");
        }

        return true;
    }

    /**
     * Get validation error message for quantity
     *
     * @param mixed $quantity
     * @param MeasurementUnit $unit
     * @return string|null
     */
    public static function getErrorMessage($quantity, MeasurementUnit $unit): ?string
    {
        if (!is_numeric($quantity)) {
            throw new QuantityValidationException("Cantidad debe ser numerica");

        }

        if ($quantity <= 0) {
            throw new QuantityValidationException("Cantidad debe ser positiva");
        }

        if (!$unit->allows_decimals && floor($quantity) != $quantity) {
            throw new QuantityValidationException("Unidad no permite decimales");
        }

        return null;
    }

    /**
     * Validate quantity with custom validation rule
     *
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters
     * @param \Illuminate\Validation\Validator $validator
     * @return bool
     */
    public static function validateQuantityForUnit($attribute, $value, $parameters, $validator): bool
    {
        if (empty($parameters[0])) {
            return true; // No unit specified, allow any valid number
        }

        $unitId = $parameters[0];
        $unit = MeasurementUnit::find($unitId);

        if (!$unit) {
            return true; // Unit not found, let other validation handle it
        }

        return self::validate($value, $unit);
    }
}