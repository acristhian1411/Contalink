<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\ValidPurchaseProofPayments;

class PurchaseStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|integer',
            'person_id' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (!is_int($value) && !is_object($value)) {
                        $fail($attribute . ' debe ser un entero o un objeto.');
                    }
                }
            ],
            'purchase_date' => 'required|date',
            'purchase_number' => 'required|string|unique:purchases',
            'purchase_details' => 'required|array',
            'proofPayments' => ['required', 'array', new ValidPurchaseProofPayments()]
        ];
    }
}
