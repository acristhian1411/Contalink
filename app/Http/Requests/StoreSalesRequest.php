<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Requests\Traits\HasStandardValidation;
use App\Rules\ValidBusinessTransaction;

class StoreSalesRequest extends FormRequest
{
    use HasStandardValidation;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user has permission to create sales
        return $this->user() && $this->user()->can('sales.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => $this->foreignKeyRule('users'),
            'till_id' => $this->foreignKeyRule('tills'),
            'person_id' => $this->foreignKeyRule('persons'),
            'sale_date' => 'required|date|before_or_equal:today',
            'sale_number' => $this->uniqueRule('sales', 'sale_number'),
            'sale_details' => ['required', 'array', 'min:1', new ValidBusinessTransaction('sale')],
            'sale_details.*.product_id' => $this->foreignKeyRule('products'),
            'sale_details.*.sd_qty' => 'required|numeric|min:0.001|max:999999.999',
            'sale_details.*.sd_amount' => 'required|numeric|min:0.01|max:999999.99',
            'proofPayments' => 'required|array|min:1',
            'proofPayments.*.amount' => 'required|numeric|min:0.01|max:999999.99',
            'proofPayments.*.value' => $this->foreignKeyRule('proof_payments'),
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge($this->getStandardMessages(), [
            'user_id.required' => 'El usuario es requerido.',
            'user_id.exists' => 'El usuario especificado no existe.',
            'till_id.required' => 'La caja es requerida.',
            'till_id.exists' => 'La caja especificada no existe.',
            'person_id.required' => 'El cliente es requerido.',
            'person_id.exists' => 'El cliente especificado no existe.',
            'sale_date.required' => 'La fecha de venta es requerida.',
            'sale_date.before_or_equal' => 'La fecha de venta no puede ser futura.',
            'sale_number.required' => 'El número de venta es requerido.',
            'sale_number.unique' => 'El número de venta ya existe.',
            'sale_details.required' => 'Los detalles de venta son requeridos.',
            'sale_details.min' => 'Debe incluir al menos un producto.',
            'sale_details.*.product_id.required' => 'El producto es requerido.',
            'sale_details.*.product_id.exists' => 'El producto especificado no existe.',
            'sale_details.*.sd_qty.required' => 'La cantidad es requerida.',
            'sale_details.*.sd_qty.min' => 'La cantidad debe ser mayor a 0.',
            'sale_details.*.sd_amount.required' => 'El precio es requerido.',
            'sale_details.*.sd_amount.min' => 'El precio debe ser mayor a 0.',
            'proofPayments.required' => 'Los métodos de pago son requeridos.',
            'proofPayments.min' => 'Debe incluir al menos un método de pago.',
        ]);
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
                'user_id', 'till_id', 'person_id', 'sale_date', 
                'sale_number', 'sale_details', 'proofPayments'
            ])->toArray();
        }
        
        return $validated;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $data = $this->all();
        $sanitized = $this->sanitizeInput($data);
        $this->replace($sanitized);
    }

    /**
     * Get fields that should be sanitized for XSS protection.
     *
     * @return array
     */
    protected function getTextFields(): array
    {
        return ['sale_notes', 'observations'];
    }
}
