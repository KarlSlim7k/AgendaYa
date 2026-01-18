<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // TODO: RBAC - Implementar permiso empleado.create
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable', 
                'email', 
                'max:255',
                Rule::unique('employees')->where(function ($query) {
                    return $query->where('business_id', $this->user()->current_business_id);
                }),
            ],
            'telefono' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9\s\-()]+$/'],
            'cargo' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', 'in:disponible,no_disponible,vacaciones,baja'],
            'avatar_url' => ['nullable', 'url', 'max:500'],
            'service_ids' => ['required', 'array', 'min:1'],
            'service_ids.*' => ['required', 'integer', 'exists:services,id'],
            'meta' => ['nullable', 'array'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del empleado es obligatorio',
            'email.email' => 'El email debe ser válido',
            'telefono.regex' => 'El teléfono debe contener solo números y caracteres válidos',
            'service_ids.required' => 'Debe asignar al menos un servicio al empleado',
            'service_ids.min' => 'Debe asignar al menos un servicio',
            'service_ids.*.exists' => 'Uno o más servicios no existen',
        ];
    }

    /**
     * Preparar datos para validación
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'business_id' => $this->user()->current_business_id,
        ]);
    }
}
