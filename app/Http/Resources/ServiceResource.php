<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
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
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'precio' => (float) $this->precio,
            'duracion_minutos' => $this->duracion_minutos,
            'buffer_pre_minutos' => $this->buffer_pre_minutos,
            'buffer_post_minutos' => $this->buffer_post_minutos,
            'requiere_confirmacion' => $this->requiere_confirmacion,
            'activo' => $this->activo,
            'meta' => $this->meta,
            'empleados_count' => $this->whenCounted('employees'),
            'empleados' => EmployeeResource::collection($this->whenLoaded('employees')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
