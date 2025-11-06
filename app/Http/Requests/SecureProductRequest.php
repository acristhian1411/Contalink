<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SecureProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user has permission to manage products
        $action = $this->isMethod('POST') ? 'products.create' : 'products.update';
        return $this->user() && $this->user()->can($action);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_name' => 'required|string|max:255|min:2',
            'product_desc' => 'nullable|string|max:1000',
            'product_cost_price' => 'required|numeric|min:0|max:999999.99',
            'product_quantity' => 'required|numeric|min:0|max:999999.999',
            'product_selling_price' => 'required|numeric|min:0|max:999999.99',
            'category_id' => 'required|integer|exists:categories,id',
            'iva_type_id' => 'required|integer|exists:iva_types,id',
            'brand_id' => 'required|integer|exists:brands,id',
            'measurement_unit_id' => 'nullable|integer|exists:measurement_units,id',
            'product_image' => 'nullable|string|max:500',
            'product_barcode' => 'nullable|string|max:100',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'product_name.required' => 'El nombre del producto es requerido.',
            'product_name.min' => 'El nombre debe tener al menos 2 caracteres.',
            'product_name.max' => 'El nombre no puede exceder 255 caracteres.',
            'product_cost_price.required' => 'El precio de costo es requerido.',
            'product_cost_price.numeric' => 'El precio de costo debe ser un número.',
            'product_cost_price.min' => 'El precio de costo no puede ser negativo.',
            'product_quantity.required' => 'La cantidad es requerida.',
            'product_quantity.numeric' => 'La cantidad debe ser un número.',
            'product_quantity.min' => 'La cantidad no puede ser negativa.',
            'product_selling_price.required' => 'El precio de venta es requerido.',
            'product_selling_price.numeric' => 'El precio de venta debe ser un número.',
            'product_selling_price.min' => 'El precio de venta no puede ser negativo.',
            'category_id.required' => 'La categoría es requerida.',
            'category_id.exists' => 'La categoría especificada no existe.',
            'iva_type_id.required' => 'El tipo de IVA es requerido.',
            'iva_type_id.exists' => 'El tipo de IVA especificado no existe.',
            'brand_id.required' => 'La marca es requerida.',
            'brand_id.exists' => 'La marca especificada no existe.',
            'measurement_unit_id.exists' => 'La unidad de medida especificada no existe.',
        ];
    }

    /**
     * Get validated data with only allowed fields.
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);
        
        // Ensure only allowed fields are returned
        if ($key === null) {
            return collect($validated)->only([
                'product_name', 'product_desc', 'product_cost_price',
                'product_quantity', 'product_selling_price', 'category_id',
                'iva_type_id', 'brand_id', 'measurement_unit_id',
                'product_image', 'product_barcode'
            ])->toArray();
        }
        
        return $validated;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Sanitize input data
        $this->merge([
            'product_name' => trim($this->product_name),
            'product_desc' => $this->product_desc ? trim($this->product_desc) : null,
            'product_barcode' => $this->product_barcode ? trim($this->product_barcode) : null,
        ]);
    }
}