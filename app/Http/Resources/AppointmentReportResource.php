<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para reportes de citas
 */
class AppointmentReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fecha_hora_inicio' => $this->fecha_hora_inicio->format('Y-m-d H:i:s'),
            'fecha_hora_fin' => $this->fecha_hora_fin->format('Y-m-d H:i:s'),
            'estado' => $this->estado,
            'cliente' => [
                'id' => $this->user->id,
                'nombre' => $this->user->nombre,
                'email' => $this->user->email,
            ],
            'servicio' => [
                'id' => $this->service->id,
                'nombre' => $this->service->nombre,
                'precio' => $this->service->precio,
            ],
            'empleado' => [
                'id' => $this->employee->id,
                'nombre' => $this->employee->nombre,
            ],
            'notas_cliente' => $this->notas_cliente,
            'fecha_creacion' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
