<?php

namespace App\Http\Requests\Service;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // TODO: RBAC - Implementar permiso servicio.create
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'precio' => ['required', 'numeric', 'min:0'],
            'duracion_minutos' => ['required', 'integer', 'min:15', 'max:480'], // Máx 8 horas
            'buffer_pre_minutos' => ['nullable', 'integer', 'min:0', 'max:120'],
            'buffer_post_minutos' => ['nullable', 'integer', 'min:0', 'max:120'],
            'requiere_confirmacion' => ['nullable', 'boolean'],
            'activo' => ['nullable', 'boolean'],
            'meta' => ['nullable', 'array'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del servicio es obligatorio',
            'precio.required' => 'El precio es obligatorio',
            'precio.min' => 'El precio debe ser mayor o igual a 0',
            'duracion_minutos.required' => 'La duración es obligatoria',
            'duracion_minutos.min' => 'La duración mínima es 15 minutos',
            'duracion_minutos.max' => 'La duración máxima es 480 minutos (8 horas)',
        ];
    }

    /**
     * Preparar datos para validación agregando business_id automáticamente
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'business_id' => $this->user()->current_business_id,
        ]);
    }
}
