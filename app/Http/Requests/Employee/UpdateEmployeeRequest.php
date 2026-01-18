<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // TODO: RBAC - Implementar permiso empleado.update
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'nombre' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9\s\-()]+$/'],
            'cargo' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', 'in:disponible,no_disponible,vacaciones,baja'],
            'avatar_url' => ['nullable', 'url', 'max:500'],
            'service_ids' => ['sometimes', 'array', 'min:1'],
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
            'service_ids.min' => 'Debe asignar al menos un servicio',
            'service_ids.*.exists' => 'Uno o más servicios no existen',
        ];
    }
}
