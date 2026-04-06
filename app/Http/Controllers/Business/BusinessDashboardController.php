<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Employee;
use App\Models\BusinessLocation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BusinessDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Obtener business_id desde current_business_id si la columna existe,
        // o desde business_user_roles como fallback (compatibilidad Neubox)
        $roleNameCol = Schema::hasColumn('roles', 'nombre') ? 'nombre' : 'name';
        $businessId = (Schema::hasColumn('users', 'current_business_id') && !empty($user->current_business_id))
            ? $user->current_business_id
            : DB::table('business_user_roles as bur')
                ->join('roles as r', 'r.id', '=', 'bur.role_id')
                ->where('bur.user_id', $user->id)
                ->whereIn('r.'.$roleNameCol, ['NEGOCIO_ADMIN', 'NEGOCIO_MANAGER', 'NEGOCIO_STAFF'])
                ->when(Schema::hasColumn('business_user_roles', 'deleted_at'), fn($q) => $q->whereNull('bur.deleted_at'))
                ->value('bur.business_id');

        if (!$businessId) {
            abort(403, 'No tienes un negocio asignado.');
        }

        $selectedPeriod = $request->query('periodo', 'week');
        $now = Carbon::now();

        match ($selectedPeriod) {
            'today' => [$startDate, $endDate] = [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'week' => [$startDate, $endDate] = [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'month' => [$startDate, $endDate] = [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'year' => [$startDate, $endDate] = [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            default => [$startDate, $endDate] = [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
        };

        $appointments = Appointment::where('business_id', $businessId)
            ->whereBetween('fecha_hora_inicio', [$startDate, $endDate])
            ->get();

        $totalAppointments = $appointments->count();
        $confirmedAppointments = $appointments->where('estado', 'confirmed')->count();
        $completedAppointments = $appointments->where('estado', 'completed')->count();
        $cancelledAppointments = $appointments->where('estado', 'cancelled')->count();
        $pendingAppointments = $appointments->where('estado', 'pending')->count();

        $revenue = $appointments->where('estado', 'completed')
            ->sum(fn($apt) => $apt->service?->precio ?? 0);

        $uniqueClients = $appointments->pluck('user_id')->filter()->unique()->count();

        $topServices = Appointment::where('business_id', $businessId)
            ->whereBetween('fecha_hora_inicio', [$startDate, $endDate])
            ->select('service_id', DB::raw('count(*) as total'))
            ->with('service:id,nombre')
            ->groupBy('service_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->filter(fn($apt) => $apt->service)
            ->map(fn($apt) => [
                'nombre' => $apt->service->nombre,
                'total' => $apt->total
            ])
            ->values()
            ->toArray();

        $employeePerformance = Appointment::where('business_id', $businessId)
            ->whereBetween('fecha_hora_inicio', [$startDate, $endDate])
            ->where('estado', 'completed')
            ->select('employee_id', DB::raw('count(*) as total'))
            ->with('employee:id,nombre')
            ->groupBy('employee_id')
            ->orderByDesc('total')
            ->get()
            ->filter(fn($apt) => $apt->employee)
            ->map(fn($apt) => [
                'nombre' => $apt->employee->nombre,
                'total' => $apt->total
            ])
            ->values()
            ->toArray();

        $upcomingAppointments = Appointment::where('business_id', $businessId)
            ->where('estado', 'confirmed')
            ->where('fecha_hora_inicio', '>=', Carbon::now())
            ->orderBy('fecha_hora_inicio')
            ->limit(10)
            ->with(['user', 'service', 'employee'])
            ->get()
            ->toArray();

        $todayAppointments = Appointment::where('business_id', $businessId)
            ->whereDate('fecha_hora_inicio', $now->toDateString())
            ->orderBy('fecha_hora_inicio')
            ->limit(5)
            ->with(['user', 'service', 'employee'])
            ->get();

        $totalServices = Service::where('business_id', $businessId)->where('activo', true)->count();
        $totalEmployees = Employee::where('business_id', $businessId)->whereNotIn('estado', ['baja'])->count();
        $totalLocations = BusinessLocation::where('business_id', $businessId)->where('activo', true)->count();

        $appointmentsByStatus = [
            'pending' => $pendingAppointments,
            'confirmed' => $confirmedAppointments,
            'completed' => $completedAppointments,
            'cancelled' => $cancelledAppointments,
        ];

        return view('business.dashboard', compact(
            'selectedPeriod',
            'totalAppointments',
            'confirmedAppointments',
            'completedAppointments',
            'cancelledAppointments',
            'pendingAppointments',
            'revenue',
            'uniqueClients',
            'topServices',
            'employeePerformance',
            'upcomingAppointments',
            'todayAppointments',
            'totalServices',
            'totalEmployees',
            'totalLocations',
            'appointmentsByStatus',
            'startDate',
            'endDate',
        ));
    }
}
