<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\BusinessUserRole;
use App\Models\NotificationLog;
use App\Models\Permission;
use App\Models\PlatformSetting;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    private const FILTERABLE_ESTADOS = ['pending', 'approved', 'suspended', 'inactive'];

    private const VALID_SECTIONS = [
        'dashboard', 'negocios', 'usuarios', 'citas',
        'notificaciones', 'jobs', 'configuracion',
        'roles', 'finanzas', 'salud',
    ];

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

            $currentSection = $request->string('seccion')->toString();
            if (! in_array($currentSection, self::VALID_SECTIONS, true)) {
                $currentSection = 'dashboard';
            }

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

            // ── Usuarios Globales ──────────────────────────────────────────────
            $globalUsers = collect();
            if ($currentSection === 'usuarios') {
                $globalUsers = User::query()
                    ->select(['id', 'nombre', 'apellidos', 'email', 'telefono', 'email_verified_at', 'created_at', 'deleted_at'])
                    ->withTrashed()
                    ->withCount(['businessRoles as roles_count' => fn ($q) => $q->withoutGlobalScopes()])
                    ->latest()
                    ->paginate(20)
                    ->withQueryString();
            }

            // ── Roles & Permisos ───────────────────────────────────────────────
            $rolesList = collect();
            if ($currentSection === 'roles') {
                $rolesList = $this->buildRolesList();
            }

            // ── Reportes Financieros ───────────────────────────────────────────
            $monthlyRevenue = collect();
            $revenueByPlan = ['basic' => 0, 'standard' => 0, 'premium' => 0];
            if ($currentSection === 'finanzas' && Schema::hasTable('appointments')) {
                $monthlyRevenue = Appointment::withoutGlobalScopes()
                    ->join('services', 'appointments.service_id', '=', 'services.id')
                    ->whereNull('appointments.deleted_at')
                    ->where('appointments.estado', Appointment::ESTADO_COMPLETED)
                    ->selectRaw('YEAR(appointments.fecha_hora_inicio) as year, MONTH(appointments.fecha_hora_inicio) as month, SUM(services.precio) as total')
                    ->groupBy(DB::raw('YEAR(appointments.fecha_hora_inicio)'), DB::raw('MONTH(appointments.fecha_hora_inicio)'))
                    ->orderBy('year')
                    ->orderBy('month')
                    ->limit(12)
                    ->get();

                Business::query()->select('meta')->get()->each(function (Business $business) use (&$revenueByPlan, $monthStart, $monthEnd) {
                    $plan = $this->resolveBusinessPlan($business->meta);
                    $revenue = Appointment::withoutGlobalScopes()
                        ->join('services', 'appointments.service_id', '=', 'services.id')
                        ->whereNull('appointments.deleted_at')
                        ->where('appointments.business_id', $business->id)
                        ->where('appointments.estado', Appointment::ESTADO_COMPLETED)
                        ->whereBetween('appointments.fecha_hora_inicio', [$monthStart, $monthEnd])
                        ->sum('services.precio');
                    $revenueByPlan[$plan] = ($revenueByPlan[$plan] ?? 0) + (float) $revenue;
                });
            }

            // ── Salud del Sistema ──────────────────────────────────────────────
            $systemHealth = [];
            if ($currentSection === 'salud') {
                $systemHealth = [
                    'php_version'    => PHP_VERSION,
                    'laravel_version'=> app()->version(),
                    'environment'    => app()->environment(),
                    'app_version'    => config('app.version', env('APP_VERSION', '1.0.0')),
                    'app_url'        => config('app.url'),
                    'timezone'       => config('app.timezone'),
                    'cache_driver'   => config('cache.default'),
                    'queue_driver'   => config('queue.default'),
                    'db_connection'  => config('database.default'),
                    'failed_jobs'    => $failedJobsTotal,
                    'is_healthy'     => $failedJobsTotal === 0,
                ];
            }

            if ($request->ajax() && $request->query('partial') === 'businesses-table') {
                return response()->view('admin.partials.businesses-table', [
                    'businessesTable' => $businessesTable,
                ]);
            }

            $sectionLabels = [
                'dashboard'      => 'Dashboard',
                'negocios'       => 'Negocios y Tenants',
                'usuarios'       => 'Usuarios Globales',
                'citas'          => 'Monitor de Citas',
                'notificaciones' => 'Log de Notificaciones',
                'jobs'           => 'Cola de Jobs',
                'configuracion'  => 'Configuracion',
                'roles'          => 'Roles y Permisos',
                'finanzas'       => 'Reportes Financieros',
                'salud'          => 'Salud del Sistema',
            ];

            return view('admin.dashboard', [
                'current_section'   => $currentSection,
                'stats'             => $stats,
                'stats_deltas'      => $statsDeltas,
                'businesses_table'  => $businessesTable,
                'chart_planes'      => $chartPlanes,
                'top_businesses'    => $topBusinesses,
                'citas_chart_data'  => $citasChartData,
                'pending_businesses'=> $pendingBusinesses,
                'notification_logs' => $notificationLogs,
                'failed_jobs'       => $failedJobs,
                'failed_jobs_total' => $failedJobsTotal,
                'platform_settings' => $platformSettings,
                'system_is_healthy' => $failedJobsTotal === 0,
                'topbar_notifications_count' => $topbarNotificationsCount,
                'global_users'      => $globalUsers,
                'roles_list'        => $rolesList,
                'monthly_revenue'   => $monthlyRevenue,
                'revenue_by_plan'   => $revenueByPlan,
                'system_health'     => $systemHealth,
                'breadcrumbs'       => [
                    ['label' => 'Plataforma', 'url' => route('admin.dashboard')],
                    ['label' => $sectionLabels[$currentSection] ?? 'Dashboard', 'url' => null],
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
                'current_section' => 'dashboard',
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
                'global_users' => collect(),
                'roles_list' => collect(),
                'monthly_revenue' => collect(),
                'revenue_by_plan' => ['basic' => 0, 'standard' => 0, 'premium' => 0],
                'system_health' => [],
                'breadcrumbs' => [
                    ['label' => 'Plataforma', 'url' => route('admin.dashboard')],
                    ['label' => 'Dashboard', 'url' => null],
                ],
                'selected_estado_filter' => null,
            ], 200);
        }
    }

    public function showBusiness(int $id)
    {
        abort_unless(Gate::allows('platform-admin'), 403, 'No autorizado.');

        $business = Business::query()
            ->withCount(['locations', 'employees'])
            ->with(['locations:id,business_id,nombre,direccion', 'employees:id,business_id,nombre'])
            ->findOrFail($id);

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $citasMes = Appointment::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->whereNull('deleted_at')
            ->whereBetween('fecha_hora_inicio', [$monthStart, $monthEnd])
            ->count();

        $citasTotales = Appointment::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->whereNull('deleted_at')
            ->count();

        $plan = $this->resolveBusinessPlan($business->meta);

        return response()->json([
            'id' => $business->id,
            'nombre' => $business->nombre,
            'email' => $business->email,
            'telefono' => $business->telefono,
            'categoria' => $business->categoria,
            'descripcion' => $business->descripcion,
            'estado' => $business->estado,
            'plan' => $plan,
            'razon_social' => $business->razon_social,
            'rfc' => $business->rfc,
            'locations_count' => $business->locations_count,
            'employees_count' => $business->employees_count,
            'citas_mes' => $citasMes,
            'citas_totales' => $citasTotales,
            'created_at' => optional($business->created_at)->format('d/m/Y H:i'),
            'updated_at' => optional($business->updated_at)->format('d/m/Y H:i'),
        ]);
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

    public function updateRoleAssignment(Request $request, int $id)
    {
        abort_unless(Gate::allows('platform-admin'), 403, 'No autorizado.');

        if (!Schema::hasTable('business_user_roles') || !Schema::hasColumn('business_user_roles', 'role_id') || !Schema::hasTable('roles')) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'No se puede actualizar la asignacion en este entorno.'], 503);
            }

            return back()->withErrors(['role_assignment' => 'No se puede actualizar la asignacion en este entorno.']);
        }

        $validated = $request->validate([
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ]);

        $assignmentQuery = BusinessUserRole::query()->withoutGlobalScopes();
        if (Schema::hasColumn('business_user_roles', 'deleted_at')) {
            $assignmentQuery->whereNull('deleted_at');
        }

        /** @var BusinessUserRole $assignment */
        $assignment = $assignmentQuery->findOrFail($id);
        $assignment->role_id = (int) $validated['role_id'];

        try {
            $assignment->save();
        } catch (QueryException $exception) {
            $isDuplicate = str_contains(strtolower($exception->getMessage()), 'duplicate')
                || str_contains(strtolower($exception->getMessage()), 'unique');

            $message = $isDuplicate
                ? 'La asignacion ya existe para ese usuario en el negocio seleccionado.'
                : 'No se pudo actualizar la asignacion.';

            if ($request->wantsJson()) {
                return response()->json(['message' => $message], $isDuplicate ? 409 : 422);
            }

            return back()->withErrors(['role_assignment' => $message]);
        }

        $role = Role::query()->find($assignment->role_id);
        $roleLabel = (string) (
            $role?->display_name
            ?? $role?->nombre
            ?? $role?->name
            ?? 'Rol actualizado'
        );

        logger()->info('Asignacion de rol actualizada desde dashboard admin', [
            'assignment_id' => $assignment->id,
            'user_id' => $assignment->user_id,
            'business_id' => $assignment->business_id,
            'new_role_id' => $assignment->role_id,
            'actor_user_id' => $request->user()?->id,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Asignacion actualizada correctamente.',
                'data' => [
                    'assignment_id' => $assignment->id,
                    'role_id' => (int) $assignment->role_id,
                    'role_label' => $roleLabel,
                ],
            ]);
        }

        return back()->with('status', 'Asignacion actualizada correctamente.');
    }

    public function destroyRoleAssignment(Request $request, int $id)
    {
        abort_unless(Gate::allows('platform-admin'), 403, 'No autorizado.');

        if (!Schema::hasTable('business_user_roles')) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'No se puede eliminar la asignacion en este entorno.'], 503);
            }

            return back()->withErrors(['role_assignment' => 'No se puede eliminar la asignacion en este entorno.']);
        }

        $assignmentQuery = BusinessUserRole::query()->withoutGlobalScopes();
        if (Schema::hasColumn('business_user_roles', 'deleted_at')) {
            $assignmentQuery->whereNull('deleted_at');
        }

        /** @var BusinessUserRole $assignment */
        $assignment = $assignmentQuery->findOrFail($id);

        $assignment->delete();

        logger()->warning('Asignacion de rol eliminada desde dashboard admin', [
            'assignment_id' => $assignment->id,
            'user_id' => $assignment->user_id,
            'business_id' => $assignment->business_id,
            'role_id' => $assignment->role_id,
            'actor_user_id' => $request->user()?->id,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Asignacion eliminada correctamente.']);
        }

        return back()->with('status', 'Asignacion eliminada correctamente.');
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

    private function buildRolesList()
    {
        if (!Schema::hasTable('roles')) {
            return collect();
        }

        $roleNameColumn = $this->resolveExistingColumn('roles', ['nombre', 'name']);
        if ($roleNameColumn === null) {
            return collect();
        }

        $roleDisplayColumn = $this->resolveExistingColumn('roles', ['display_name', $roleNameColumn]);
        $roleDescriptionColumn = $this->resolveExistingColumn('roles', ['descripcion', 'description']);
        $roleHierarchyColumn = $this->resolveExistingColumn('roles', ['nivel_jerarquia']);
        $permissionNameColumn = Schema::hasTable('permissions')
            ? $this->resolveExistingColumn('permissions', ['nombre', 'name'])
            : null;

        $rolesQuery = Role::query();

        $canLoadAssignments = Schema::hasTable('business_user_roles')
            && Schema::hasColumn('business_user_roles', 'role_id');
        $hasDeletedAtInAssignments = $canLoadAssignments
            && Schema::hasColumn('business_user_roles', 'deleted_at');
        $canLoadAssignmentUsers = $canLoadAssignments
            && Schema::hasTable('users')
            && Schema::hasColumn('business_user_roles', 'user_id');
        $canLoadAssignmentBusinesses = $canLoadAssignments
            && Schema::hasTable('businesses')
            && Schema::hasColumn('business_user_roles', 'business_id');
        $assignmentDateColumn = $canLoadAssignments
            ? $this->resolveExistingColumn('business_user_roles', ['asignado_el', 'created_at'])
            : null;

        if ($canLoadAssignments) {
            $rolesQuery->withCount([
                'businessUserRoles as asignaciones_count' => fn ($query) => $query->withoutGlobalScopes(),
            ]);

            $rolesQuery->with([
                'businessUserRoles' => function ($query) use (
                    $hasDeletedAtInAssignments,
                    $assignmentDateColumn,
                    $canLoadAssignmentUsers,
                    $canLoadAssignmentBusinesses
                ) {
                    $query->withoutGlobalScopes();

                    if ($hasDeletedAtInAssignments) {
                        $query->whereNull('deleted_at');
                    }

                    if ($assignmentDateColumn !== null) {
                        $query->orderByDesc($assignmentDateColumn);
                    }

                    if ($canLoadAssignmentUsers) {
                        $query->with('user');
                    }

                    if ($canLoadAssignmentBusinesses) {
                        $query->with('business');
                    }
                },
            ]);
        }

        $canLoadPermissions = Schema::hasTable('permissions')
            && Schema::hasTable('role_permissions')
            && Schema::hasColumn('role_permissions', 'role_id')
            && Schema::hasColumn('role_permissions', 'permission_id');

        if ($canLoadPermissions) {
            $rolesQuery->with('permissions');
        }

        if ($roleHierarchyColumn !== null) {
            $rolesQuery->orderBy($roleHierarchyColumn);
        } else {
            $rolesQuery->orderBy('id');
        }

        return $rolesQuery->get()->map(function (Role $role) use (
            $roleNameColumn,
            $roleDisplayColumn,
            $roleDescriptionColumn,
            $roleHierarchyColumn,
            $permissionNameColumn,
            $canLoadPermissions,
            $canLoadAssignments
        ) {
            $roleName = (string) ($role->getAttribute($roleNameColumn) ?? '');
            $roleDisplayName = (string) ($role->getAttribute($roleDisplayColumn) ?? $roleName);

            $role->setAttribute('nombre_ui', $roleName);
            $role->setAttribute('display_name_ui', $roleDisplayName !== '' ? $roleDisplayName : $roleName);
            $role->setAttribute('descripcion_ui', $roleDescriptionColumn ? $role->getAttribute($roleDescriptionColumn) : null);
            $role->setAttribute('nivel_jerarquia_ui', (int) ($roleHierarchyColumn ? ($role->getAttribute($roleHierarchyColumn) ?? 0) : 0));

            $assignmentsUi = collect();
            if ($canLoadAssignments && $role->relationLoaded('businessUserRoles')) {
                $assignmentsUi = $role->businessUserRoles
                    ->map(function (BusinessUserRole $assignment) use ($roleDisplayName) {
                        $user = $assignment->relationLoaded('user') ? $assignment->user : null;
                        $business = $assignment->relationLoaded('business') ? $assignment->business : null;

                        $firstName = (string) ($user?->nombre ?? $user?->name ?? '');
                        $lastName = (string) ($user?->apellidos ?? '');
                        $fullName = trim(implode(' ', array_filter([$firstName, $lastName], fn (string $part) => $part !== '')));
                        $fallbackUserName = (string) (
                            $user?->email
                            ?? ((isset($assignment->user_id) && $assignment->user_id)
                                ? 'Usuario #' . $assignment->user_id
                                : 'Usuario sin referencia')
                        );
                        $businessName = (string) (
                            $business?->nombre
                            ?? ((isset($assignment->business_id) && $assignment->business_id)
                                ? 'Negocio #' . $assignment->business_id
                                : 'N/A')
                        );

                        $assignedAtRaw = $assignment->asignado_el ?? $assignment->created_at;
                        $assignedAt = $assignedAtRaw instanceof \DateTimeInterface
                            ? Carbon::instance($assignedAtRaw)->toIso8601String()
                            : null;

                        return [
                            'id' => (int) $assignment->id,
                            'user_id' => (int) ($assignment->user_id ?? 0),
                            'user_name' => $fullName !== '' ? $fullName : $fallbackUserName,
                            'user_email' => (string) ($user?->email ?? ''),
                            'business_id' => isset($assignment->business_id) ? (int) $assignment->business_id : null,
                            'business_name' => $businessName,
                            'role_id' => (int) ($assignment->role_id ?? 0),
                            'role_name' => $roleDisplayName,
                            'asignado_el' => $assignedAt,
                        ];
                    })
                    ->values();
            }

            $role->setAttribute('asignados_ui', $assignmentsUi->all());
            $role->setAttribute('asignaciones_count', (int) ($role->asignaciones_count ?? $assignmentsUi->count()));

            if (!$canLoadPermissions) {
                $role->setRelation('permissions', collect());

                return $role;
            }

            $permissions = $role->permissions
                ->map(function (Permission $permission) use ($permissionNameColumn) {
                    $permissionName = (string) ($permissionNameColumn
                        ? ($permission->getAttribute($permissionNameColumn) ?? '')
                        : '');

                    $permission->setAttribute('nombre_ui', $permissionName);

                    return $permission;
                })
                ->filter(fn (Permission $permission) => $permission->getAttribute('nombre_ui') !== '')
                ->values();

            $role->setRelation('permissions', $permissions);

            return $role;
        });
    }

    private function resolveExistingColumn(string $table, array $candidates): ?string
    {
        foreach ($candidates as $column) {
            if ($column !== null && Schema::hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    private function calculateDelta(float|int $current, float|int $previous): float
    {
        if ((float) $previous === 0.0) {
            return (float) $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
