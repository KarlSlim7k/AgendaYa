<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\NotificationLog;
use App\Models\PlatformSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    private const FILTERABLE_ESTADOS = ['pending', 'approved', 'suspended', 'inactive'];

    private const SETTING_KEYS = [
        'email_notifications_enabled',
        'whatsapp_notifications_enabled',
        'require_email_verification',
        'appointment_reminder_24h',
        'appointment_reminder_1h',
    ];

    public function index(Request $request)
    {
        try {
            abort_unless(Gate::allows('platform-admin'), 403, 'No autorizado.');

            $today = now()->startOfDay();
            $monthStart = now()->startOfMonth();
            $monthEnd = now()->endOfMonth();
            $previousMonthStart = now()->subMonthNoOverflow()->startOfMonth();
            $previousMonthEnd = now()->subMonthNoOverflow()->endOfMonth();

            $estadoFilter = $request->string('estado')->toString();
            $estadoFilter = in_array($estadoFilter, self::FILTERABLE_ESTADOS, true) ? $estadoFilter : null;

            $stats = [
                'total_businesses' => Business::query()
                    ->where('estado', 'approved')
                    ->count(),

                'citas_hoy' => Appointment::withoutGlobalScopes()
                    ->whereDate('fecha_hora_inicio', $today)
                    ->count(),

                'total_users' => User::query()->count(),

                // El esquema actual no tiene precio_final en appointments, por eso se suma services.precio.
                'ingresos_mes' => Appointment::withoutGlobalScopes()
                    ->join('services', 'appointments.service_id', '=', 'services.id')
                    ->whereNull('appointments.deleted_at')
                    ->where('appointments.estado', Appointment::ESTADO_COMPLETED)
                    ->whereBetween('appointments.fecha_hora_inicio', [$monthStart, $monthEnd])
                    ->sum('services.precio'),
            ];

            $previousStats = [
                'total_businesses' => Business::query()
                    ->where('estado', 'approved')
                    ->where('created_at', '<', $monthStart)
                    ->count(),

                'citas_hoy' => Appointment::withoutGlobalScopes()
                    ->whereBetween('fecha_hora_inicio', [$previousMonthStart, $previousMonthEnd])
                    ->count(),

                'total_users' => User::query()
                    ->where('created_at', '<', $monthStart)
                    ->count(),

                'ingresos_mes' => Appointment::withoutGlobalScopes()
                    ->join('services', 'appointments.service_id', '=', 'services.id')
                    ->whereNull('appointments.deleted_at')
                    ->where('appointments.estado', Appointment::ESTADO_COMPLETED)
                    ->whereBetween('appointments.fecha_hora_inicio', [$previousMonthStart, $previousMonthEnd])
                    ->sum('services.precio'),
            ];

            $statsDeltas = [
                'total_businesses' => $this->calculateDelta($stats['total_businesses'], $previousStats['total_businesses']),
                'citas_hoy' => $this->calculateDelta($stats['citas_hoy'], $previousStats['citas_hoy']),
                'total_users' => $this->calculateDelta($stats['total_users'], $previousStats['total_users']),
                'ingresos_mes' => $this->calculateDelta((float) $stats['ingresos_mes'], (float) $previousStats['ingresos_mes']),
            ];

            $businessesTable = Business::query()
                ->select(['id', 'nombre', 'estado', 'meta', 'created_at'])
                ->when($estadoFilter, fn ($query) => $query->where('estado', $estadoFilter))
                ->withCount([
                    'locations',
                    'employees',
                    'appointments as citas_mes_count' => function ($query) use ($monthStart, $monthEnd) {
                        $query->withoutGlobalScopes()
                            ->whereBetween('fecha_hora_inicio', [$monthStart, $monthEnd]);
                    },
                ])
                ->latest('created_at')
                ->paginate(10)
                ->withQueryString();

            $businessesTable->getCollection()->transform(function (Business $business) {
                $business->plan = $this->resolveBusinessPlan($business->meta);

                return $business;
            });

            $chartPlanes = ['basic' => 0, 'standard' => 0, 'premium' => 0];
            Business::query()->select('meta')->get()->each(function (Business $business) use (&$chartPlanes) {
                $plan = $this->resolveBusinessPlan($business->meta);
                $chartPlanes[$plan] = ($chartPlanes[$plan] ?? 0) + 1;
            });

            $topBusinesses = Business::query()
                ->select('businesses.id', 'businesses.nombre')
                ->leftJoin('appointments', function ($join) use ($monthStart, $monthEnd) {
                    $join->on('appointments.business_id', '=', 'businesses.id')
                        ->whereNull('appointments.deleted_at')
                        ->where('appointments.estado', Appointment::ESTADO_COMPLETED)
                        ->whereBetween('appointments.fecha_hora_inicio', [$monthStart, $monthEnd]);
                })
                ->selectRaw('COUNT(appointments.id) as citas_completadas')
                ->groupBy('businesses.id', 'businesses.nombre')
                ->orderByDesc('citas_completadas')
                ->limit(5)
                ->get();

            $citasChartData = $this->buildAppointmentsChartData(90);

            $pendingBusinesses = Business::query()
                ->where('estado', 'pending')
                ->latest('created_at')
                ->limit(8)
                ->get(['id', 'nombre', 'categoria', 'created_at']);

            $notificationLogs = collect();
            $topbarNotificationsCount = 0;
            if (Schema::hasTable('notification_logs')) {
                $notificationLogs = NotificationLog::query()
                    ->withoutGlobalScopes()
                    ->with('user:id,email')
                    ->latest('created_at')
                    ->limit(8)
                    ->get();

                $topbarNotificationsCount = NotificationLog::query()
                    ->withoutGlobalScopes()
                    ->whereDate('created_at', now())
                    ->count();
            }

            $failedJobsTotal = 0;
            $failedJobs = collect();
            if (Schema::hasTable('failed_jobs')) {
                $failedJobsTotal = DB::table('failed_jobs')->count();
                $failedJobs = DB::table('failed_jobs')
                    ->select('id', 'queue', 'failed_at')
                    ->orderByDesc('failed_at')
                    ->limit(5)
                    ->get();
            }

            $platformSettings = collect();
            if (Schema::hasTable('platform_settings')) {
                $platformSettings = PlatformSetting::query()
                    ->whereIn('clave', self::SETTING_KEYS)
                    ->get()
                    ->keyBy('clave');
            }

            if ($request->ajax() && $request->query('partial') === 'businesses-table') {
                return response()->view('admin.partials.businesses-table', [
                    'businessesTable' => $businessesTable,
                ]);
            }

            return view('admin.dashboard', [
                'stats' => $stats,
                'stats_deltas' => $statsDeltas,
                'businesses_table' => $businessesTable,
                'chart_planes' => $chartPlanes,
                'top_businesses' => $topBusinesses,
                'citas_chart_data' => $citasChartData,
                'pending_businesses' => $pendingBusinesses,
                'notification_logs' => $notificationLogs,
                'failed_jobs' => $failedJobs,
                'failed_jobs_total' => $failedJobsTotal,
                'platform_settings' => $platformSettings,
                'system_is_healthy' => $failedJobsTotal === 0,
                'topbar_notifications_count' => $topbarNotificationsCount,
                'breadcrumbs' => [
                    ['label' => 'Plataforma', 'url' => route('admin.dashboard')],
                    ['label' => 'Dashboard', 'url' => null],
                ],
                'selected_estado_filter' => $estadoFilter,
            ]);
        } catch (\Throwable $exception) {
            logger()->error('Error al renderizar admin dashboard, activando modo seguro.', [
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            $emptyBusinessesTable = new LengthAwarePaginator(
                collect(),
                0,
                10,
                max(1, (int) $request->query('page', 1)),
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );

            if ($request->ajax() && $request->query('partial') === 'businesses-table') {
                return response()->view('admin.partials.businesses-table', [
                    'businessesTable' => $emptyBusinessesTable,
                ]);
            }

            return response()->view('admin.dashboard', [
                'stats' => [
                    'total_businesses' => 0,
                    'citas_hoy' => 0,
                    'total_users' => 0,
                    'ingresos_mes' => 0,
                ],
                'stats_deltas' => [
                    'total_businesses' => 0,
                    'citas_hoy' => 0,
                    'total_users' => 0,
                    'ingresos_mes' => 0,
                ],
                'businesses_table' => $emptyBusinessesTable,
                'chart_planes' => ['basic' => 0, 'standard' => 0, 'premium' => 0],
                'top_businesses' => collect(),
                'citas_chart_data' => [
                    'labels' => [],
                    'confirmed' => [],
                    'completed' => [],
                    'cancelled' => [],
                    'no_show' => [],
                    'generated_at' => Carbon::now()->toIso8601String(),
                ],
                'pending_businesses' => collect(),
                'notification_logs' => collect(),
                'failed_jobs' => collect(),
                'failed_jobs_total' => 0,
                'platform_settings' => collect(),
                'system_is_healthy' => false,
                'topbar_notifications_count' => 0,
                'breadcrumbs' => [
                    ['label' => 'Plataforma', 'url' => route('admin.dashboard')],
                    ['label' => 'Dashboard', 'url' => null],
                ],
                'selected_estado_filter' => null,
            ], 200);
        }
    }

    public function approveBusiness(Request $request, int $id)
    {
        abort_unless(Gate::allows('platform-admin'), 403, 'No autorizado.');

        $business = Business::query()->findOrFail($id);
        $business->update(['estado' => 'approved']);

        logger()->info('Business aprobado por plataforma', [
            'business_id' => $business->id,
            'actor_user_id' => $request->user()?->id,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Negocio aprobado correctamente.']);
        }

        return back()->with('status', 'Negocio aprobado correctamente.');
    }

    public function suspendBusiness(Request $request, int $id)
    {
        abort_unless(Gate::allows('platform-admin'), 403, 'No autorizado.');

        $business = Business::query()->findOrFail($id);
        $business->update(['estado' => 'suspended']);

        logger()->warning('Business suspendido por plataforma', [
            'business_id' => $business->id,
            'actor_user_id' => $request->user()?->id,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Negocio suspendido correctamente.']);
        }

        return back()->with('status', 'Negocio suspendido correctamente.');
    }

    public function updateSettings(Request $request)
    {
        abort_unless(Gate::allows('platform-admin'), 403, 'No autorizado.');

        if (!Schema::hasTable('platform_settings')) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Tabla platform_settings no disponible en este entorno.'], 503);
            }

            return back()->withErrors(['settings' => 'No se puede guardar: falta la tabla platform_settings.']);
        }

        $validated = $request->validate([
            'settings' => ['required', 'array'],
        ]);

        $incomingSettings = collect($validated['settings'])
            ->only(self::SETTING_KEYS)
            ->all();

        foreach ($incomingSettings as $clave => $value) {
            $setting = PlatformSetting::query()->firstOrNew(['clave' => $clave]);

            if (!$setting->exists) {
                $setting->tipo = 'boolean';
                $setting->descripcion = 'Configuracion administrable desde dashboard de plataforma';
                $setting->editable = true;
            }

            if (!$setting->editable) {
                continue;
            }

            $setting->valor = $this->castSettingValue($value, $setting->tipo ?? 'boolean');
            $setting->save();
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Configuraciones actualizadas.']);
        }

        return back()->with('status', 'Configuraciones guardadas.');
    }

    public function retryFailedJob(Request $request, int $id)
    {
        abort_unless(Gate::allows('platform-admin'), 403, 'No autorizado.');

        if (!Schema::hasTable('failed_jobs')) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Tabla failed_jobs no disponible en este entorno.'], 503);
            }

            return back()->withErrors(['failed_jobs' => 'No se puede reintentar: falta la tabla failed_jobs.']);
        }

        $exists = DB::table('failed_jobs')->where('id', $id)->exists();
        abort_unless($exists, 404, 'Job no encontrado.');

        Artisan::call('queue:retry', ['id' => $id]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Job enviado a reintento.']);
        }

        return back()->with('status', "Job #{$id} enviado a reintento.");
    }

    private function resolveBusinessPlan(mixed $meta): string
    {
        $plan = data_get($meta, 'plan', 'basic');

        if (!is_string($plan)) {
            return 'basic';
        }

        $normalized = strtolower($plan);

        return in_array($normalized, ['basic', 'standard', 'premium'], true)
            ? $normalized
            : 'basic';
    }

    private function castSettingValue(mixed $value, string $tipo): string
    {
        return match ($tipo) {
            'integer' => (string) ((int) $value),
            'json' => json_encode($value, JSON_UNESCAPED_UNICODE) ?: '{}',
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
            default => (string) $value,
        };
    }

    private function buildAppointmentsChartData(int $days): array
    {
        if (!Schema::hasTable('appointments')) {
            return [
                'labels' => [],
                'confirmed' => [],
                'completed' => [],
                'cancelled' => [],
                'no_show' => [],
                'generated_at' => Carbon::now()->toIso8601String(),
            ];
        }

        $rangeDays = max(30, $days);
        $endDate = now()->endOfDay();
        $startDate = now()->subDays($rangeDays - 1)->startOfDay();

        $statuses = [
            Appointment::ESTADO_CONFIRMED,
            Appointment::ESTADO_COMPLETED,
            Appointment::ESTADO_CANCELLED,
            Appointment::ESTADO_NO_SHOW,
        ];

        $rows = Appointment::withoutGlobalScopes()
            ->selectRaw('DATE(fecha_hora_inicio) as fecha, estado, COUNT(*) as total')
            ->whereBetween('fecha_hora_inicio', [$startDate, $endDate])
            ->whereIn('estado', $statuses)
            ->groupBy(DB::raw('DATE(fecha_hora_inicio)'), 'estado')
            ->orderBy('fecha')
            ->get();

        $mapped = [];
        foreach ($rows as $row) {
            $mapped[$row->fecha][$row->estado] = (int) $row->total;
        }

        $labels = [];
        $series = [
            'confirmed' => [],
            'completed' => [],
            'cancelled' => [],
            'no_show' => [],
        ];

        for ($cursor = $startDate->copy(); $cursor->lte($endDate); $cursor->addDay()) {
            $dateKey = $cursor->toDateString();
            $labels[] = $dateKey;

            $series['confirmed'][] = (int) ($mapped[$dateKey][Appointment::ESTADO_CONFIRMED] ?? 0);
            $series['completed'][] = (int) ($mapped[$dateKey][Appointment::ESTADO_COMPLETED] ?? 0);
            $series['cancelled'][] = (int) ($mapped[$dateKey][Appointment::ESTADO_CANCELLED] ?? 0);
            $series['no_show'][] = (int) ($mapped[$dateKey][Appointment::ESTADO_NO_SHOW] ?? 0);
        }

        return [
            'labels' => $labels,
            'confirmed' => $series['confirmed'],
            'completed' => $series['completed'],
            'cancelled' => $series['cancelled'],
            'no_show' => $series['no_show'],
            'generated_at' => Carbon::now()->toIso8601String(),
        ];
    }

    private function calculateDelta(float|int $current, float|int $previous): float
    {
        if ((float) $previous === 0.0) {
            return (float) $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
