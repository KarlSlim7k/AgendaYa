<?php

namespace App\Http\Resources\Public;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessPublicResource extends JsonResource
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
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'telefono' => $this->telefono,
            'email' => $this->email,
            'categoria' => $this->categoria,
            'estado' => $this->estado,
            
            // Incluir sucursales activas si están cargadas
            'locations' => BusinessLocationResource::collection($this->whenLoaded('activeLocations')),
            
            // Información adicional para búsqueda
            'total_services' => $this->whenCounted('services'),
            'total_employees' => $this->whenCounted('employees'),
            
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
