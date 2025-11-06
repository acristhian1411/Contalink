<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Requests\Traits\HasStandardValidation;
use App\Rules\SecureFileUpload;

class SecureFileUploadRequest extends FormRequest
{
    use HasStandardValidation;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $uploadType = $this->input('upload_type', 'image');
        
        return [
            'file' => $this->getFileValidationRule($uploadType),
            'upload_type' => 'required|string|in:image,document,avatar',
            'description' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get file validation rule based on upload type.
     */
    protected function getFileValidationRule(string $uploadType): array
    {
        switch ($uploadType) {
            case 'image':
                return [
                    'required',
                    new SecureFileUpload(['jpeg', 'png', 'jpg', 'gif'], 2048)
                ];
            
            case 'document':
                return [
                    'required',
                    new SecureFileUpload(['pdf', 'doc', 'docx'], 5120)
                ];
            
            case 'avatar':
                return [
                    'required',
                    new SecureFileUpload(['jpeg', 'png', 'jpg'], 1024)
                ];
            
            default:
                return [
                    'required',
                    new SecureFileUpload(['jpeg', 'png', 'jpg', 'gif', 'pdf'], 2048)
                ];
        }
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge($this->getStandardMessages(), [
            'file.required' => 'Debe seleccionar un archivo.',
            'upload_type.required' => 'El tipo de archivo es requerido.',
            'upload_type.in' => 'Tipo de archivo no válido.',
            'description.max' => 'La descripción no puede exceder 500 caracteres.',
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
        
        if ($key === null) {
            return collect($validated)->only([
                'file', 'upload_type', 'description'
            ])->toArray();
        }
        
        return $validated;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('description')) {
            $this->merge([
                'description' => trim($this->description)
            ]);
        }
    }
}