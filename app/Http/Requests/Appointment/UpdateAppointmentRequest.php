<?php

namespace App\Http\Requests\Appointment;

use App\Models\Appointment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Se validará en el Policy
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'estado' => [
                'sometimes',
                'string',
                Rule::in([
                    Appointment::ESTADO_PENDING,
                    Appointment::ESTADO_CONFIRMED,
                    Appointment::ESTADO_COMPLETED,
                    Appointment::ESTADO_CANCELLED,
                    Appointment::ESTADO_NO_SHOW,
                ])
            ],
            'notas_internas' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'motivo_cancelacion' => ['sometimes', 'nullable', 'string', 'max:500'],
            'custom_data' => ['sometimes', 'nullable', 'array'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'estado.in' => 'El estado no es válido',
            'notas_internas.max' => 'Las notas internas no pueden exceder 1000 caracteres',
            'motivo_cancelacion.max' => 'El motivo de cancelación no puede exceder 500 caracteres',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'estado' => 'estado de la cita',
            'notas_internas' => 'notas internas',
            'motivo_cancelacion' => 'motivo de cancelación',
        ];
    }
}
