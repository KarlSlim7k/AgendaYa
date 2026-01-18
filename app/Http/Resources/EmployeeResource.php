<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
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
            'email' => $this->email,
            'telefono' => $this->telefono,
            'avatar_url' => $this->avatar_url,
            'cargo' => $this->cargo,
            'estado' => $this->estado,
            'meta' => $this->meta,
            'servicios_count' => $this->services_count ?? $this->services->count(),
            'servicios' => ServiceResource::collection($this->whenLoaded('services')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
