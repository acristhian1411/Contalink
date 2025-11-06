<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class SecureUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user has permission to manage users
        return $this->user() && $this->user()->can('users.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('id');
        
        return [
            'name' => 'required|string|max:255|min:2',
            'email' => 'required|email|max:255|unique:users,email,' . $userId,
            'password' => $this->isMethod('POST') 
                ? ['required', Password::min(8)->mixedCase()->numbers()->symbols()]
                : ['nullable', Password::min(8)->mixedCase()->numbers()->symbols()],
            'person_id' => 'nullable|integer|exists:persons,id',
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
            'name.required' => 'El nombre es requerido.',
            'name.min' => 'El nombre debe tener al menos 2 caracteres.',
            'name.max' => 'El nombre no puede exceder 255 caracteres.',
            'email.required' => 'El email es requerido.',
            'email.email' => 'El email debe tener un formato válido.',
            'email.unique' => 'Este email ya está registrado.',
            'password.required' => 'La contraseña es requerida.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'person_id.exists' => 'La persona especificada no existe.',
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
            $allowedFields = ['name', 'email', 'person_id'];
            
            // Only include password if it's provided
            if (!empty($validated['password'])) {
                $allowedFields[] = 'password';
            }
            
            return collect($validated)->only($allowedFields)->toArray();
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
            'name' => trim($this->name),
            'email' => strtolower(trim($this->email)),
        ]);
    }
}