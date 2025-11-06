<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SecureBasicRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Basic authorization - user must be authenticated
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Get the controller name to determine validation rules
        $controller = $this->route()->getController();
        $controllerClass = get_class($controller);
        
        // Extract controller name (e.g., CategoriesController -> categories)
        $controllerName = strtolower(str_replace('Controller', '', class_basename($controllerClass)));
        
        return $this->getValidationRules($controllerName);
    }

    /**
     * Get validation rules based on controller type.
     *
     * @param string $controllerName
     * @return array
     */
    protected function getValidationRules(string $controllerName): array
    {
        $rules = [
            'categories' => [
                'cat_desc' => 'required|string|max:255|min:2',
            ],
            'brands' => [
                'brand_name' => 'required|string|max:255|min:2',
                'brand_desc' => 'nullable|string|max:500',
            ],
            'cities' => [
                'city_name' => 'required|string|max:255|min:2',
                'city_code' => 'nullable|string|max:10',
                'state_id' => 'required|integer|exists:states,id',
            ],
            'countries' => [
                'country_name' => 'required|string|max:255|min:2',
                'country_code' => 'nullable|string|max:10',
            ],
            'states' => [
                'state_name' => 'required|string|max:255|min:2',
                'country_id' => 'required|integer|exists:countries,id',
            ],
            'contacttypes' => [
                'cont_type_desc' => 'required|string|max:255|min:2',
            ],
            'persontypes' => [
                'p_type_desc' => 'required|string|max:255|min:2',
            ],
            'ivatypes' => [
                'iva_type_desc' => 'required|string|max:255|min:2',
                'iva_type_percent' => 'required|numeric|min:0|max:100',
            ],
            'paymenttypes' => [
                'payment_type_desc' => 'required|string|max:255|min:2',
            ],
            'tilltypes' => [
                'till_type_desc' => 'required|string|max:255|min:2',
            ],
            'accountplans' => [
                'account_desc' => 'required|string|max:255|min:2',
                'account_code' => 'nullable|string|max:20',
            ],
        ];

        return $rules[$controllerName] ?? [
            'name' => 'required|string|max:255|min:2',
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
            'required' => 'Este campo es requerido.',
            'string' => 'Este campo debe ser texto.',
            'max' => 'Este campo no puede exceder :max caracteres.',
            'min' => 'Este campo debe tener al menos :min caracteres.',
            'numeric' => 'Este campo debe ser un número.',
            'integer' => 'Este campo debe ser un número entero.',
            'exists' => 'El valor seleccionado no es válido.',
        ];
    }

    /**
     * Get validated data with sanitized input.
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);
        
        // Sanitize string fields
        if ($key === null && is_array($validated)) {
            foreach ($validated as $field => $value) {
                if (is_string($value)) {
                    $validated[$field] = trim($value);
                }
            }
        }
        
        return $validated;
    }
}