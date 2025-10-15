<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\ValidSaleProofPayments;

class StoreSalesRequest extends FormRequest
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
            'till_id' => 'required|integer',
            'person_id' => 'required|integer',
            'sale_date' => 'required|date',
            'sale_number' => 'required|string|unique:sales',
            'sale_details' => 'required|array',
            'proofPayments' => ['required', 'array', new ValidSaleProofPayments()]
        ];
    }
}
