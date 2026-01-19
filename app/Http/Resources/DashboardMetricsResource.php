<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para métricas del dashboard
 */
class DashboardMetricsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'citas' => [
                'hoy' => $this->resource['citas']['hoy'],
                'semana' => $this->resource['citas']['semana'],
                'mes' => $this->resource['citas']['mes'],
            ],
            'ingresos' => [
                'mes' => $this->resource['ingresos']['mes'],
                'promedio_cita' => $this->resource['ingresos']['promedio_cita'],
                'citas_completadas' => $this->resource['ingresos']['citas_completadas'],
            ],
            'clientes' => [
                'unicos_mes' => $this->resource['clientes']['unicos_mes'],
                'nuevos_mes' => $this->resource['clientes']['nuevos_mes'],
                'recurrentes' => $this->resource['clientes']['recurrentes'],
            ],
            'operacion' => [
                'tasa_ocupacion' => $this->resource['operacion']['tasa_ocupacion'],
                'tasa_cancelacion' => $this->resource['operacion']['tasa_cancelacion'],
                'tasa_no_show' => $this->resource['operacion']['tasa_no_show'],
            ],
            'top_servicios' => $this->resource['top_servicios'],
            'empleado_destacado' => $this->resource['empleado_destacado'],
        ];
    }
}
