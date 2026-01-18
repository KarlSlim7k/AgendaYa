<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'user_id' => $this->user_id,
            'employee_id' => $this->employee_id,
            'service_id' => $this->service_id,
            
            // Datos principales
            'fecha_hora_inicio' => $this->fecha_hora_inicio->toIso8601String(),
            'fecha_hora_fin' => $this->fecha_hora_fin->toIso8601String(),
            'estado' => $this->estado,
            
            // Notas
            'notas_cliente' => $this->notas_cliente,
            'notas_internas' => $this->when(
                $this->canViewInternalNotes($request),
                $this->notas_internas
            ),
            'motivo_cancelacion' => $this->motivo_cancelacion,
            'custom_data' => $this->custom_data,
            
            // Timestamps de gestión
            'confirmada_en' => $this->confirmada_en?->toIso8601String(),
            'completada_en' => $this->completada_en?->toIso8601String(),
            'cancelada_en' => $this->cancelada_en?->toIso8601String(),
            'cancelada_por_user_id' => $this->cancelada_por_user_id,
            
            // Relaciones
            'business' => new BusinessResource($this->whenLoaded('business')),
            'user' => new UserResource($this->whenLoaded('user')),
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'service' => new ServiceResource($this->whenLoaded('service')),
            'cancelled_by' => new UserResource($this->whenLoaded('cancelledBy')),
            
            // Timestamps estándar
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }

    /**
     * Determinar si el usuario puede ver notas internas
     */
    private function canViewInternalNotes(Request $request): bool
    {
        $user = $request->user();
        
        if (!$user) {
            return false;
        }

        // Admin o staff del negocio pueden ver notas internas
        return $user->hasPermissionInBusiness('cita.read', $this->business_id);
    }
}
