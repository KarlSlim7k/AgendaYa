<?php

namespace App\Http\Requests\Appointment;

use App\Models\Appointment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Usuario autenticado puede crear citas (USUARIO_FINAL o roles de negocio)
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'business_id' => ['required', 'integer', 'exists:businesses,id'],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'fecha_hora_inicio' => ['required', 'date', 'after:now'],
            'notas_cliente' => ['nullable', 'string', 'max:1000'],
            'custom_data' => ['nullable', 'array'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validar que el empleado pertenece al negocio
            if ($this->business_id && $this->employee_id) {
                $employee = \App\Models\Employee::find($this->employee_id);
                if ($employee && $employee->business_id !== $this->business_id) {
                    $validator->errors()->add('employee_id', 'El empleado no pertenece al negocio seleccionado');
                }
            }

            // Validar que el servicio pertenece al negocio
            if ($this->business_id && $this->service_id) {
                $service = \App\Models\Service::find($this->service_id);
                if ($service && $service->business_id !== $this->business_id) {
                    $validator->errors()->add('service_id', 'El servicio no pertenece al negocio seleccionado');
                }
            }

            // Validar que el empleado puede realizar el servicio
            if ($this->employee_id && $this->service_id) {
                $canPerform = \App\Models\Employee::find($this->employee_id)
                    ?->services()
                    ->where('services.id', $this->service_id)
                    ->exists();
                
                if (!$canPerform) {
                    $validator->errors()->add('employee_id', 'El empleado no está capacitado para realizar este servicio');
                }
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'business_id.required' => 'El negocio es obligatorio',
            'business_id.exists' => 'El negocio seleccionado no existe',
            'service_id.required' => 'El servicio es obligatorio',
            'service_id.exists' => 'El servicio seleccionado no existe',
            'employee_id.required' => 'El empleado es obligatorio',
            'employee_id.exists' => 'El empleado seleccionado no existe',
            'fecha_hora_inicio.required' => 'La fecha y hora de inicio es obligatoria',
            'fecha_hora_inicio.date' => 'La fecha y hora debe tener formato válido',
            'fecha_hora_inicio.after' => 'La cita debe ser para una fecha futura',
            'notas_cliente.max' => 'Las notas no pueden exceder 1000 caracteres',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'business_id' => 'negocio',
            'service_id' => 'servicio',
            'employee_id' => 'empleado',
            'fecha_hora_inicio' => 'fecha y hora',
            'notas_cliente' => 'notas del cliente',
        ];
    }
}
