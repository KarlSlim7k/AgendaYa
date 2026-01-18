<?php

namespace App\Http\Requests\Schedule;

use Illuminate\Foundation\Http\FormRequest;

class StoreScheduleExceptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // TODO: RBAC - Implementar permiso sucursal.update
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'fecha' => ['required', 'date', 'after_or_equal:today'],
            'tipo' => ['required', 'in:feriado,vacaciones,cierre'],
            'todo_el_dia' => ['nullable', 'boolean'],
            'hora_inicio' => ['required_if:todo_el_dia,false', 'nullable', 'date_format:H:i'],
            'hora_fin' => ['required_if:todo_el_dia,false', 'nullable', 'date_format:H:i', 'after:hora_inicio'],
            'motivo' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'fecha.required' => 'La fecha es obligatoria',
            'fecha.after_or_equal' => 'La fecha no puede ser anterior a hoy',
            'tipo.required' => 'El tipo de excepción es obligatorio',
            'tipo.in' => 'El tipo debe ser: feriado, vacaciones o cierre',
            'hora_inicio.required_if' => 'La hora de inicio es obligatoria cuando no es todo el día',
            'hora_fin.required_if' => 'La hora de fin es obligatoria cuando no es todo el día',
            'hora_fin.after' => 'La hora de fin debe ser posterior a la hora de inicio',
        ];
    }
}
