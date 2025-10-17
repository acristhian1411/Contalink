<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MeasurementUnitRequest extends FormRequest
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
        $rules = [
            'unit_name' => 'required|string|max:50',
            'unit_abbreviation' => 'required|string|max:10',
            'allows_decimals' => 'boolean',
            'is_active' => 'boolean'
        ];

        // For updates, we need to exclude the current record from unique validation
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['unit_name'] .= '|unique:measurement_units,unit_name,' . $this->route('id');
        } else {
            $rules['unit_name'] .= '|unique:measurement_units,unit_name';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'unit_name.required' => 'El nombre de la unidad es requerido.',
            'unit_name.unique' => 'Ya existe una unidad de medida con este nombre.',
            'unit_name.max' => 'El nombre de la unidad no puede exceder 50 caracteres.',
            'unit_abbreviation.required' => 'La abreviación es requerida.',
            'unit_abbreviation.max' => 'La abreviación no puede exceder 10 caracteres.',
            'allows_decimals.boolean' => 'El campo permite decimales debe ser verdadero o falso.',
            'is_active.boolean' => 'El campo activo debe ser verdadero o falso.'
        ];
    }
}