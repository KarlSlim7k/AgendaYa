<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Service para generar métricas y reportes de negocio
 * 
 * Calcula:
 * - Métricas de citas (hoy, semana, mes)
 * - Tasa de ocupación
 * - Ingresos y promedios
 * - Clientes únicos, nuevos, recurrentes
 * - Tasas de cancelación y no-show
 * - Servicios más solicitados
 * - Empleado más ocupado
 */
class ReportsService
{
    /**
     * Obtener dashboard completo de métricas para un negocio
     */
    public function getDashboardMetrics(Business $business): array
    {
        return [
            'citas' => $this->getAppointmentMetrics($business),
            'ingresos' => $this->getRevenueMetrics($business),
            'clientes' => $this->getClientMetrics($business),
            'operacion' => $this->getOperationalMetrics($business),
            'top_servicios' => $this->getTopServices($business, 5),
            'empleado_destacado' => $this->getTopEmployee($business),
        ];
    }

    /**
     * Métricas de citas
     */
    public function getAppointmentMetrics(Business $business): array
    {
        $now = Carbon::now();
        $startOfWeek = $now->copy()->startOfWeek();
        $startOfMonth = $now->copy()->startOfMonth();

        return [
            'hoy' => Appointment::where('business_id', $business->id)
                ->whereDate('fecha_hora_inicio', $now->toDateString())
                ->whereIn('estado', ['pending', 'confirmed'])
                ->count(),
            
            'semana' => Appointment::where('business_id', $business->id)
                ->whereBetween('fecha_hora_inicio', [$startOfWeek, $now->copy()->endOfWeek()])
                ->whereIn('estado', ['pending', 'confirmed', 'completed'])
                ->count(),
            
            'mes' => Appointment::where('business_id', $business->id)
                ->whereBetween('fecha_hora_inicio', [$startOfMonth, $now->copy()->endOfMonth()])
                ->whereIn('estado', ['pending', 'confirmed', 'completed'])
                ->count(),
        ];
    }

    /**
     * Métricas de ingresos
     */
    public function getRevenueMetrics(Business $business): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $monthlyAppointments = Appointment::where('business_id', $business->id)
            ->whereBetween('fecha_hora_inicio', [$startOfMonth, $endOfMonth])
            ->where('estado', 'completed')
            ->with('service:id,precio')
            ->get();

        $ingresosMes = $monthlyAppointments->sum(function ($appointment) {
            return $appointment->service->precio ?? 0;
        });

        $citasCompletadas = $monthlyAppointments->count();

        return [
            'mes' => round($ingresosMes, 2),
            'promedio_cita' => $citasCompletadas > 0 
                ? round($ingresosMes / $citasCompletadas, 2) 
                : 0,
            'citas_completadas' => $citasCompletadas,
        ];
    }

    /**
     * Métricas de clientes
     */
    public function getClientMetrics(Business $business): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        // Clientes únicos del mes
        $clientesUnicosMes = Appointment::where('business_id', $business->id)
            ->whereBetween('fecha_hora_inicio', [$startOfMonth, $endOfMonth])
            ->distinct('user_id')
            ->count('user_id');

        // Clientes nuevos (primera cita este mes)
        $clientesNuevos = Appointment::where('business_id', $business->id)
            ->whereBetween('fecha_hora_inicio', [$startOfMonth, $endOfMonth])
            ->whereNotExists(function ($query) use ($business, $startOfMonth) {
                $query->select(DB::raw(1))
                    ->from('appointments as prev')
                    ->whereColumn('prev.user_id', 'appointments.user_id')
                    ->where('prev.business_id', $business->id)
                    ->where('prev.fecha_hora_inicio', '<', $startOfMonth);
            })
            ->distinct('user_id')
            ->count('user_id');

        // Clientes recurrentes (2+ citas)
        $clientesRecurrentes = Appointment::where('business_id', $business->id)
            ->select('user_id', DB::raw('COUNT(*) as citas_count'))
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) >= 2')
            ->count();

        return [
            'unicos_mes' => $clientesUnicosMes,
            'nuevos_mes' => $clientesNuevos,
            'recurrentes' => $clientesRecurrentes,
        ];
    }

    /**
     * Métricas operacionales
     */
    public function getOperationalMetrics(Business $business): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $allAppointments = Appointment::where('business_id', $business->id)
            ->whereBetween('fecha_hora_inicio', [$startOfMonth, $endOfMonth])
            ->get();

        $total = $allAppointments->count();
        $cancelled = $allAppointments->where('estado', 'cancelled')->count();
        $noShow = $allAppointments->where('estado', 'no_show')->count();

        // Tasa de ocupación (citas confirmadas/completadas vs slots disponibles)
        // Simplificado: ratio de citas vs días del mes
        $diasMes = $startOfMonth->diffInDays($endOfMonth) + 1;
        $citasConfirmadas = $allAppointments->whereIn('estado', ['confirmed', 'completed'])->count();
        
        // Asumiendo 8 slots por día (ejemplo)
        $slotsDisponibles = $diasMes * 8;
        $tasaOcupacion = $slotsDisponibles > 0 
            ? round(($citasConfirmadas / $slotsDisponibles) * 100, 2) 
            : 0;

        return [
            'tasa_ocupacion' => $tasaOcupacion,
            'tasa_cancelacion' => $total > 0 ? round(($cancelled / $total) * 100, 2) : 0,
            'tasa_no_show' => $total > 0 ? round(($noShow / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Top servicios más solicitados
     */
    public function getTopServices(Business $business, int $limit = 5): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        return Appointment::withoutGlobalScope('business')
            ->where('appointments.business_id', $business->id)
            ->whereBetween('appointments.fecha_hora_inicio', [$startOfMonth, $endOfMonth])
            ->joinSub(
                \App\Models\Service::withoutGlobalScope('business')
                    ->select('id', 'nombre', 'precio', 'business_id'),
                'services',
                function ($join) use ($business) {
                    $join->on('appointments.service_id', '=', 'services.id')
                         ->where('services.business_id', '=', $business->id);
                }
            )
            ->select(
                'services.id as servicio_id',
                'services.nombre',
                DB::raw('COUNT(appointments.id) as cantidad'),
                DB::raw('SUM(services.precio) as ingresos')
            )
            ->groupBy('services.id', 'services.nombre')
            ->orderByDesc('cantidad')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'servicio_id' => $item->servicio_id,
                    'nombre' => $item->nombre,
                    'cantidad' => $item->cantidad,
                    'ingresos' => round($item->ingresos, 2),
                ];
            })
            ->toArray();
    }

    /**
     * Empleado más ocupado del mes
     */
    public function getTopEmployee(Business $business): ?array
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $topEmployee = Appointment::withoutGlobalScope('business')
            ->where('appointments.business_id', $business->id)
            ->whereBetween('appointments.fecha_hora_inicio', [$startOfMonth, $endOfMonth])
            ->whereIn('appointments.estado', ['confirmed', 'completed'])
            ->joinSub(
                \App\Models\Employee::withoutGlobalScope('business')
                    ->select('id', 'nombre', 'business_id'),
                'employees',
                function ($join) use ($business) {
                    $join->on('appointments.employee_id', '=', 'employees.id')
                         ->where('employees.business_id', '=', $business->id);
                }
            )
            ->select(
                'employees.id',
                'employees.nombre',
                DB::raw('COUNT(appointments.id) as citas_mes')
            )
            ->groupBy('employees.id', 'employees.nombre')
            ->orderByDesc('citas_mes')
            ->first();

        if (!$topEmployee) {
            return null;
        }

        return [
            'id' => $topEmployee->id,
            'nombre' => $topEmployee->nombre,
            'citas_mes' => $topEmployee->citas_mes,
        ];
    }

    /**
     * Reporte de citas con filtros
     */
    public function getAppointmentsReport(
        ?Business $business,
        ?Carbon $fechaInicio = null,
        ?Carbon $fechaFin = null,
        ?int $serviceId = null,
        ?int $employeeId = null,
        ?string $estado = null
    ) {
        $query = Appointment::where('appointments.business_id', $business?->id ?? 0)
            ->with(['user:id,nombre,email', 'service:id,nombre,precio', 'employee:id,nombre']);

        if ($fechaInicio) {
            $query->where('fecha_hora_inicio', '>=', $fechaInicio);
        }

        if ($fechaFin) {
            $query->where('fecha_hora_inicio', '<=', $fechaFin);
        }

        if ($serviceId) {
            $query->where('service_id', $serviceId);
        }

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        if ($estado) {
            $query->where('estado', $estado);
        }

        return $query->orderByDesc('fecha_hora_inicio');
    }

    /**
     * Generar datos para gráfico de citas por día (últimos 30 días)
     */
    public function getAppointmentsChartData(Business $business): array
    {
        $endDate = Carbon::now();
        $startDate = $endDate->copy()->subDays(29);

        $appointments = Appointment::where('business_id', $business->id)
            ->whereBetween('fecha_hora_inicio', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(fecha_hora_inicio) as fecha'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get()
            ->pluck('total', 'fecha')
            ->toArray();

        // Rellenar días sin citas con 0
        $labels = [];
        $data = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = $endDate->copy()->subDays($i)->toDateString();
            $labels[] = Carbon::parse($date)->format('d M');
            $data[] = $appointments[$date] ?? 0;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }
}
