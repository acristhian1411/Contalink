<?php

namespace App\Http\Requests\Traits;

use Illuminate\Validation\Rules\Password;

trait HasStandardValidation
{
    /**
     * Get standard validation rules for common data types.
     *
     * @return array
     */
    protected function getStandardRules(): array
    {
        return [
            // Text fields
            'short_text' => 'required|string|max:255|min:2',
            'medium_text' => 'required|string|max:500|min:2',
            'long_text' => 'required|string|max:1000|min:2',
            'optional_short_text' => 'nullable|string|max:255',
            'optional_medium_text' => 'nullable|string|max:500',
            'optional_long_text' => 'nullable|string|max:1000',
            
            // Numeric fields
            'positive_integer' => 'required|integer|min:1',
            'non_negative_integer' => 'required|integer|min:0',
            'positive_decimal' => 'required|numeric|min:0.01',
            'non_negative_decimal' => 'required|numeric|min:0',
            'percentage' => 'required|numeric|min:0|max:100',
            'price' => 'required|numeric|min:0|max:999999.99',
            'quantity' => 'required|numeric|min:0.001|max:999999.999',
            
            // Date fields
            'date_today_or_past' => 'required|date|before_or_equal:today',
            'date_future' => 'required|date|after:today',
            'date_past' => 'required|date|before:today',
            'optional_date' => 'nullable|date',
            
            // Email and identifiers
            'email' => 'required|email|max:255',
            'unique_email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', Password::min(8)->mixedCase()->numbers()->symbols()],
            'optional_password' => ['nullable', Password::min(8)->mixedCase()->numbers()->symbols()],
            
            // File uploads
            'image_upload' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'document_upload' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
            
            // Foreign keys
            'foreign_key' => 'required|integer|exists:{{table}},id',
            'optional_foreign_key' => 'nullable|integer|exists:{{table}},id',
            
            // Codes and identifiers
            'code' => 'nullable|string|max:20|alpha_num',
            'barcode' => 'nullable|string|max:100|alpha_num',
            'unique_code' => 'required|string|max:50|unique:{{table}},{{field}}',
            
            // XSS protected fields
            'safe_text' => ['required', 'string', 'max:255', new \App\Rules\NoXss()],
            'safe_content' => ['required', 'string', 'max:1000', new \App\Rules\NoXss(true)],
            'strict_text' => ['required', 'string', 'max:255', new \App\Rules\NoXss(false)],
        ];
    }

    /**
     * Get business-specific validation rules.
     *
     * @return array
     */
    protected function getBusinessRules(): array
    {
        return [
            // Person identification
            'person_id_number' => 'required|string|max:20|regex:/^[0-9\-]+$/',
            'person_ruc' => 'nullable|string|max:20|regex:/^[0-9\-]+$/',
            
            // Product codes
            'product_barcode' => 'nullable|string|max:100|regex:/^[0-9A-Za-z\-]+$/',
            
            // Financial amounts
            'sale_amount' => 'required|numeric|min:0.01|max:999999.99',
            'purchase_amount' => 'required|numeric|min:0.01|max:999999.99',
            'payment_amount' => 'required|numeric|min:0.01|max:999999.99',
            
            // Document numbers
            'sale_number' => 'required|string|max:50|regex:/^[A-Z0-9\-]+$/',
            'purchase_number' => 'required|string|max:50|regex:/^[A-Z0-9\-]+$/',
            
            // Quantities based on measurement units
            'product_quantity_unit' => 'required|numeric|min:0.001|max:999999.999',
            'product_quantity_kg' => 'required|numeric|min:0.001|max:999999.999',
            'product_quantity_piece' => 'required|integer|min:1|max:999999',
        ];
    }

    /**
     * Get standard error messages in Spanish.
     *
     * @return array
     */
    protected function getStandardMessages(): array
    {
        return [
            'required' => 'Este campo es requerido.',
            'string' => 'Este campo debe ser texto.',
            'max' => 'Este campo no puede exceder :max caracteres.',
            'min' => 'Este campo debe tener al menos :min caracteres.',
            'numeric' => 'Este campo debe ser un número.',
            'integer' => 'Este campo debe ser un número entero.',
            'exists' => 'El valor seleccionado no es válido.',
            'unique' => 'Este valor ya está registrado.',
            'email' => 'Debe ser una dirección de email válida.',
            'date' => 'Debe ser una fecha válida.',
            'before' => 'La fecha debe ser anterior a :date.',
            'after' => 'La fecha debe ser posterior a :date.',
            'before_or_equal' => 'La fecha no puede ser futura.',
            'image' => 'El archivo debe ser una imagen.',
            'mimes' => 'El archivo debe ser de tipo: :values.',
            'file' => 'Debe seleccionar un archivo válido.',
            'regex' => 'El formato no es válido.',
            'alpha_num' => 'Solo se permiten letras y números.',
        ];
    }

    /**
     * Sanitize input data before validation.
     *
     * @param array $data
     * @return array
     */
    protected function sanitizeInput(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = $this->sanitizeStringField($value, $key);
            } elseif (is_array($value)) {
                $data[$key] = $this->sanitizeInput($value);
            }
        }
        
        return $data;
    }

    /**
     * Sanitize a string field based on its type.
     *
     * @param string $value
     * @param string $key
     * @return string
     */
    protected function sanitizeStringField(string $value, string $key): string
    {
        // Remove null bytes
        $value = str_replace("\0", '', $value);
        
        // Trim whitespace
        $value = trim($value);
        
        // Apply field-specific sanitization
        if ($this->isEmailField($key)) {
            return \App\Utils\InputSanitizer::sanitizeEmail($value);
        }
        
        if ($this->isUrlField($key)) {
            return \App\Utils\InputSanitizer::sanitizeUrl($value);
        }
        
        if ($this->isNumericField($key)) {
            return \App\Utils\InputSanitizer::sanitizeNumeric($value);
        }
        
        if ($this->isCodeField($key)) {
            return \App\Utils\InputSanitizer::sanitizeCode($value);
        }
        
        if ($this->isPhoneField($key)) {
            return \App\Utils\InputSanitizer::sanitizePhone($value);
        }
        
        if (in_array($key, $this->getTextFields())) {
            return \App\Utils\InputSanitizer::sanitizeTextContent($value);
        }
        
        // Default sanitization
        return \App\Utils\InputSanitizer::sanitizeString($value);
    }

    /**
     * Check if field is an email field.
     */
    protected function isEmailField(string $key): bool
    {
        return str_contains(strtolower($key), 'email') || 
               str_contains(strtolower($key), 'mail');
    }

    /**
     * Check if field is a URL field.
     */
    protected function isUrlField(string $key): bool
    {
        return str_contains(strtolower($key), 'url') || 
               str_contains(strtolower($key), 'link') ||
               str_contains(strtolower($key), 'website');
    }

    /**
     * Check if field is numeric.
     */
    protected function isNumericField(string $key): bool
    {
        $numericPatterns = ['price', 'amount', 'qty', 'quantity', 'number', 'count'];
        
        foreach ($numericPatterns as $pattern) {
            if (str_contains(strtolower($key), $pattern)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if field is a code field.
     */
    protected function isCodeField(string $key): bool
    {
        $codeFields = ['code', 'barcode', 'sku', 'reference'];
        
        foreach ($codeFields as $field) {
            if (str_contains(strtolower($key), $field)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if field is a phone field.
     */
    protected function isPhoneField(string $key): bool
    {
        return str_contains(strtolower($key), 'phone') || 
               str_contains(strtolower($key), 'tel') ||
               str_contains(strtolower($key), 'mobile');
    }

    /**
     * Get fields that should be sanitized for XSS protection.
     *
     * @return array
     */
    protected function getTextFields(): array
    {
        return [
            'product_desc', 'person_address', 'notes', 'description',
            'comments', 'observations', 'remarks'
        ];
    }

    /**
     * Get validation rule for foreign key with specific table.
     *
     * @param string $table
     * @param string $field
     * @param bool $required
     * @return string
     */
    protected function foreignKeyRule(string $table, string $field = 'id', bool $required = true): string
    {
        $rule = $required ? 'required' : 'nullable';
        return "{$rule}|integer|exists:{$table},{$field}";
    }

    /**
     * Get validation rule for unique field with specific table and field.
     *
     * @param string $table
     * @param string $field
     * @param mixed $ignoreId
     * @return string
     */
    protected function uniqueRule(string $table, string $field, $ignoreId = null): string
    {
        $rule = "required|string|max:255|unique:{$table},{$field}";
        
        if ($ignoreId) {
            $rule .= ",{$ignoreId}";
        }
        
        return $rule;
    }

    /**
     * Validate file upload with specific constraints.
     *
     * @param array $allowedMimes
     * @param int $maxSizeKb
     * @param bool $required
     * @return string
     */
    protected function fileUploadRule(array $allowedMimes, int $maxSizeKb = 2048, bool $required = false): string
    {
        $rule = $required ? 'required' : 'nullable';
        $mimes = implode(',', $allowedMimes);
        
        return "{$rule}|file|mimes:{$mimes}|max:{$maxSizeKb}";
    }
}