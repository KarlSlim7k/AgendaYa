<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleExceptionResource extends JsonResource
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
            'business_location_id' => $this->business_location_id,
            'fecha' => $this->fecha->format('Y-m-d'),
            'tipo' => $this->tipo,
            'todo_el_dia' => $this->todo_el_dia,
            'hora_inicio' => $this->hora_inicio,
            'hora_fin' => $this->hora_fin,
            'motivo' => $this->motivo,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
