<?php

namespace App\Http\Requests\Schedule;

use Illuminate\Foundation\Http\FormRequest;

class StoreScheduleTemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // TODO: RBAC - $this->user()->can('sucursal.update');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'dia_semana' => ['required', 'integer', 'between:0,6'],
            'hora_apertura' => ['required', 'date_format:H:i'],
            'hora_cierre' => ['required', 'date_format:H:i', 'after:hora_apertura'],
            'activo' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'dia_semana.required' => 'El día de la semana es obligatorio',
            'dia_semana.between' => 'El día debe estar entre 0 (Domingo) y 6 (Sábado)',
            'hora_apertura.required' => 'La hora de apertura es obligatoria',
            'hora_apertura.date_format' => 'La hora de apertura debe tener formato HH:MM',
            'hora_cierre.required' => 'La hora de cierre es obligatoria',
            'hora_cierre.after' => 'La hora de cierre debe ser posterior a la hora de apertura',
        ];
    }
}
