<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Requests\Traits\HasStandardValidation;
use App\Rules\ValidBusinessTransaction;

class PurchaseStoreRequest extends FormRequest
{
    use HasStandardValidation;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user has permission to create purchases
        return $this->user() && $this->user()->can('purchases.create');
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
            'person_id' => $this->foreignKeyRule('persons'),
            'till_id' => $this->foreignKeyRule('tills'),
            'purchase_date' => 'required|date|before_or_equal:today',
            'purchase_number' => $this->uniqueRule('purchases', 'purchase_number'),
            'purchase_details' => ['required', 'array', 'min:1', new ValidBusinessTransaction('purchase')],
            'purchase_details.*.product_id' => $this->foreignKeyRule('products'),
            'purchase_details.*.pd_qty' => 'required|numeric|min:0.001|max:999999.999',
            'purchase_details.*.pd_amount' => 'required|numeric|min:0.01|max:999999.99',
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
            'person_id.required' => 'El proveedor es requerido.',
            'person_id.exists' => 'El proveedor especificado no existe.',
            'till_id.required' => 'La caja es requerida.',
            'till_id.exists' => 'La caja especificada no existe.',
            'purchase_date.required' => 'La fecha de compra es requerida.',
            'purchase_date.before_or_equal' => 'La fecha de compra no puede ser futura.',
            'purchase_number.required' => 'El número de compra es requerido.',
            'purchase_number.unique' => 'El número de compra ya existe.',
            'purchase_details.required' => 'Los detalles de compra son requeridos.',
            'purchase_details.min' => 'Debe incluir al menos un producto.',
            'purchase_details.*.product_id.required' => 'El producto es requerido.',
            'purchase_details.*.product_id.exists' => 'El producto especificado no existe.',
            'purchase_details.*.pd_qty.required' => 'La cantidad es requerida.',
            'purchase_details.*.pd_qty.min' => 'La cantidad debe ser mayor a 0.',
            'purchase_details.*.pd_amount.required' => 'El precio es requerido.',
            'purchase_details.*.pd_amount.min' => 'El precio debe ser mayor a 0.',
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
                'user_id', 'person_id', 'till_id', 'purchase_date', 
                'purchase_number', 'purchase_details', 'proofPayments'
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
        return ['purchase_notes', 'observations'];
    }
}
