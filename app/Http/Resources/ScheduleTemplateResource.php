<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleTemplateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $diasSemana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        
        return [
            'id' => $this->id,
            'business_location_id' => $this->business_location_id,
            'dia_semana' => $this->dia_semana,
            'dia_semana_nombre' => $diasSemana[$this->dia_semana] ?? '',
            'hora_apertura' => $this->hora_apertura,
            'hora_cierre' => $this->hora_cierre,
            'activo' => $this->activo,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
