<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SecurePersonRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user has permission to manage persons
        $action = $this->isMethod('POST') ? 'persons.create' : 'persons.update';
        return $this->user() && $this->user()->can($action);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $personId = $this->route('id');
        
        return [
            'person_fname' => 'required|string|max:255|min:2',
            'person_lastname' => 'required|string|max:255|min:2',
            'person_corpname' => 'nullable|string|max:255',
            'person_idnumber' => 'required|string|max:20|unique:persons,person_idnumber,' . $personId,
            'person_ruc' => 'nullable|string|max:20|unique:persons,person_ruc,' . $personId,
            'person_birtdate' => 'nullable|date|before:today',
            'person_photo' => 'nullable|string|max:500',
            'person_address' => 'nullable|string|max:500',
            'p_type_id' => 'required|integer|exists:person_types,id',
            'country_id' => 'required|integer|exists:countries,id',
            'city_id' => 'required|integer|exists:cities,id',
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
            'person_fname.required' => 'El nombre es requerido.',
            'person_fname.min' => 'El nombre debe tener al menos 2 caracteres.',
            'person_fname.max' => 'El nombre no puede exceder 255 caracteres.',
            'person_lastname.required' => 'El apellido es requerido.',
            'person_lastname.min' => 'El apellido debe tener al menos 2 caracteres.',
            'person_lastname.max' => 'El apellido no puede exceder 255 caracteres.',
            'person_idnumber.required' => 'El número de identificación es requerido.',
            'person_idnumber.unique' => 'Este número de identificación ya está registrado.',
            'person_ruc.unique' => 'Este RUC ya está registrado.',
            'person_birtdate.date' => 'La fecha de nacimiento debe ser una fecha válida.',
            'person_birtdate.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
            'p_type_id.required' => 'El tipo de persona es requerido.',
            'p_type_id.exists' => 'El tipo de persona especificado no existe.',
            'country_id.required' => 'El país es requerido.',
            'country_id.exists' => 'El país especificado no existe.',
            'city_id.required' => 'La ciudad es requerida.',
            'city_id.exists' => 'La ciudad especificada no existe.',
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
                'person_fname', 'person_lastname', 'person_corpname',
                'person_idnumber', 'person_ruc', 'person_birtdate',
                'person_photo', 'person_address', 'p_type_id',
                'country_id', 'city_id'
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
            'person_fname' => trim($this->person_fname),
            'person_lastname' => trim($this->person_lastname),
            'person_corpname' => $this->person_corpname ? trim($this->person_corpname) : null,
            'person_idnumber' => trim($this->person_idnumber),
            'person_ruc' => $this->person_ruc ? trim($this->person_ruc) : null,
            'person_address' => $this->person_address ? trim($this->person_address) : null,
        ]);
    }
}