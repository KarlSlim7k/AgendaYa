@extends('layouts.admin')

@section('title', 'Dashboard')
@section('section_label', 'Plataforma')

@section('content')
    @php
        $kpis = [
            [
                'key' => 'total_businesses',
                'label' => 'Total de negocios activos',
                'value' => number_format($stats['total_businesses'] ?? 0),
                'icon' => 'businesses',
            ],
            [
                'key' => 'citas_hoy',
                'label' => 'Citas hoy',
                'value' => number_format($stats['citas_hoy'] ?? 0),
                'icon' => 'appointments',
            ],
            [
                'key' => 'total_users',
                'label' => 'Usuarios registrados',
                'value' => number_format($stats['total_users'] ?? 0),
                'icon' => 'users',
            ],
            [
                'key' => 'ingresos_mes',
                'label' => 'Ingresos estimados del mes',
                'value' => '$' . number_format((float) ($stats['ingresos_mes'] ?? 0), 2),
                'icon' => 'revenue',
            ],
        ];

        $settingCards = [
            [
                'key' => 'email_notifications_enabled',
                'label' => 'Email notifications',
                'description' => 'Envia correos automaticos de confirmacion y cambios.',
            ],
            [
                'key' => 'whatsapp_notifications_enabled',
                'label' => 'WhatsApp notifications',
                'description' => 'Habilita mensajes de WhatsApp para recordatorios.',
            ],
            [
                'key' => 'require_email_verification',
                'label' => 'Verificacion de email',
                'description' => 'Solicita email verificado antes de reservar.',
            ],
            [
                'key' => 'appointment_reminder_24h',
                'label' => 'Recordatorio 24h',
                'description' => 'Activa recordatorios 24 horas antes de la cita.',
            ],
            [
                'key' => 'appointment_reminder_1h',
                'label' => 'Recordatorio 1h',
                'description' => 'Activa recordatorios 1 hora antes de la cita.',
            ],
        ];

        $initialSettings = [];
        foreach ($settingCards as $settingCard) {
            $initialSettings[$settingCard['key']] = (bool) ($platform_settings[$settingCard['key']]->parsed_value ?? false);
        }

        $planesData = [
            'basic' => (int) ($chart_planes['basic'] ?? 0),
            'standard' => (int) ($chart_planes['standard'] ?? 0),
            'premium' => (int) ($chart_planes['premium'] ?? 0),
        ];

        $notificationTypeBadge = [
            'email' => 'bg-blue-500/15 text-blue-200 ring-1 ring-blue-400/30',
            'whatsapp' => 'bg-emerald-500/15 text-emerald-200 ring-1 ring-emerald-400/30',
            'sms' => 'bg-orange-500/15 text-orange-200 ring-1 ring-orange-400/30',
        ];

        $notificationStatusBadge = [
            'enviado' => 'bg-emerald-500/15 text-emerald-200 ring-1 ring-emerald-400/30',
            'fallido' => 'bg-rose-500/15 text-rose-200 ring-1 ring-rose-400/30',
            'reintentado' => 'bg-amber-500/15 text-amber-200 ring-1 ring-amber-400/30',
        ];
    @endphp

    <div
        x-data="adminDashboard({
            dashboardUrl: @js(route('admin.dashboard')),
            settingsUrl: @js(route('admin.settings.update')),
            approveRouteTemplate: @js(route('admin.businesses.approve', ['id' => '__ID__'])),
            suspendRouteTemplate: @js(route('admin.businesses.suspend', ['id' => '__ID__'])),
            csrfToken: @js(csrf_token()),
            selectedEstado: @js($selected_estado_filter ?? 'all'),
            initialSettings: @js($initialSettings),
            planesData: @js($planesData),
            citasData: @js($citas_chart_data),
        })"
        x-init="init()"
        class="space-y-6"
    >
        @if (session('status'))
            <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200" role="status">
                {{ session('status') }}
            </div>
        @endif

        <section aria-label="KPI cards">
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($kpis as $kpi)
                    @php
                        $delta = (float) ($stats_deltas[$kpi['key']] ?? 0);
                        $deltaPositive = $delta >= 0;
                    @endphp

                    <article class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 shadow-lg shadow-slate-950/40">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">{{ $kpi['label'] }}</p>
                                <p class="mt-2 text-3xl font-extrabold tracking-tight text-white">{{ $kpi['value'] }}</p>
                            </div>

                            <div class="rounded-xl border border-indigo-400/30 bg-indigo-500/15 p-2.5 text-indigo-200">
                                @if ($kpi['icon'] === 'businesses')
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M3 4.25A2.25 2.25 0 0 1 5.25 2h9.5A2.25 2.25 0 0 1 17 4.25v11.5A2.25 2.25 0 0 1 14.75 18h-9.5A2.25 2.25 0 0 1 3 15.75V4.25Z" /></svg>
                                @elseif ($kpi['icon'] === 'appointments')
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.75A2.25 2.25 0 0 1 18 6.25v9.5A2.25 2.25 0 0 1 15.75 18h-11.5A2.25 2.25 0 0 1 2 15.75v-9.5A2.25 2.25 0 0 1 4.25 4H5V2.75A.75.75 0 0 1 5.75 2Z" /></svg>
                                @elseif ($kpi['icon'] === 'users')
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 2a4 4 0 1 0 0 8 4 4 0 0 0 0-8ZM4 14a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-2Z" /></svg>
                                @else
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M2 5.75A2.75 2.75 0 0 1 4.75 3h10.5A2.75 2.75 0 0 1 18 5.75v8.5A2.75 2.75 0 0 1 15.25 17H4.75A2.75 2.75 0 0 1 2 14.25v-8.5Zm5.25 1.5a.75.75 0 0 0 0 1.5h5.5a.75.75 0 0 0 0-1.5h-5.5Z" /></svg>
                                @endif
                            </div>
                        </div>

                        <div class="mt-4 inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold {{ $deltaPositive ? 'bg-emerald-500/15 text-emerald-200' : 'bg-rose-500/15 text-rose-200' }}">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                @if ($deltaPositive)
                                    <path fill-rule="evenodd" d="M10 3.75a.75.75 0 0 1 .53.22l4 4a.75.75 0 1 1-1.06 1.06l-2.72-2.72V15.5a.75.75 0 0 1-1.5 0V6.31L6.53 9.03a.75.75 0 1 1-1.06-1.06l4-4A.75.75 0 0 1 10 3.75Z" clip-rule="evenodd" />
                                @else
                                    <path fill-rule="evenodd" d="M10 16.25a.75.75 0 0 1-.53-.22l-4-4a.75.75 0 1 1 1.06-1.06l2.72 2.72V4.5a.75.75 0 0 1 1.5 0v9.19l2.72-2.72a.75.75 0 1 1 1.06 1.06l-4 4a.75.75 0 0 1-.53.22Z" clip-rule="evenodd" />
                                @endif
                            </svg>
                            <span>{{ number_format($delta, 1) }}% vs mes anterior</span>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <section id="negocios-tenants" aria-label="Estado de negocios por plan">
            <div class="grid gap-6 xl:grid-cols-2">
                <article class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 shadow-lg shadow-slate-950/40">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-bold text-white">Negocios por plan y estado</h3>
                            <p class="text-sm text-slate-400">Vista operativa de tenants y volumen de citas del mes actual.</p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2" role="tablist" aria-label="Filtros de estado de negocio">
                            <button type="button" @click="filterBusinesses('all')" :class="estadoBtnClass('all')" class="rounded-lg px-3 py-1.5 text-xs font-semibold" aria-label="Filtrar todos">Todos</button>
                            <button type="button" @click="filterBusinesses('pending')" :class="estadoBtnClass('pending')" class="rounded-lg px-3 py-1.5 text-xs font-semibold" aria-label="Filtrar pendientes">Pending</button>
                            <button type="button" @click="filterBusinesses('approved')" :class="estadoBtnClass('approved')" class="rounded-lg px-3 py-1.5 text-xs font-semibold" aria-label="Filtrar aprobados">Approved</button>
                            <button type="button" @click="filterBusinesses('suspended')" :class="estadoBtnClass('suspended')" class="rounded-lg px-3 py-1.5 text-xs font-semibold" aria-label="Filtrar suspendidos">Suspended</button>
                        </div>
                    </div>

                    <div class="relative mt-4">
                        <div x-show="businessesLoading" class="absolute inset-0 z-10 flex items-center justify-center rounded-xl bg-slate-950/70 text-sm font-semibold text-slate-200">
                            Cargando negocios...
                        </div>

                        <div id="businesses-table-wrapper" x-ref="businessesTable">
                            @include('admin.partials.businesses-table', ['businessesTable' => $businesses_table])
                        </div>
                    </div>
                </article>

                <div class="space-y-6">
                    <article class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 shadow-lg shadow-slate-950/40">
                        <h3 class="text-lg font-bold text-white">Distribucion de planes</h3>
                        <p class="text-sm text-slate-400">Participacion actual por tipo de suscripcion.</p>
                        <div class="mt-4 h-72">
                            <canvas x-ref="planesChart" aria-label="Grafica de distribucion de planes" role="img"></canvas>
                        </div>
                    </article>

                    <article class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 shadow-lg shadow-slate-950/40">
                        <h3 class="text-lg font-bold text-white">Top 5 negocios por citas completadas</h3>
                        <p class="text-sm text-slate-400">Ranking de productividad mensual.</p>

                        @if ($top_businesses->count() > 0)
                            <ul class="mt-4 space-y-3">
                                @foreach ($top_businesses as $index => $business)
                                    <li class="flex items-center justify-between rounded-xl border border-slate-800 bg-slate-950/50 px-3 py-2.5">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-white">{{ $index + 1 }}. {{ $business->nombre }}</p>
                                            <p class="text-xs text-slate-400">Citas completadas este mes</p>
                                        </div>
                                        <span class="rounded-full bg-indigo-500/15 px-2.5 py-1 text-xs font-semibold text-indigo-200">{{ (int) $business->citas_completadas }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="mt-4 rounded-xl border border-dashed border-slate-700 bg-slate-950/40 p-6 text-center">
                                <p class="text-sm font-semibold text-slate-200">Aun no hay citas completadas este mes.</p>
                                <p class="mt-1 text-xs text-slate-400">El ranking aparecera automaticamente cuando haya datos.</p>
                            </div>
                        @endif
                    </article>
                </div>
            </div>
        </section>

        <section id="monitor-citas" aria-label="Monitor de citas en tiempo real">
            <article class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 shadow-lg shadow-slate-950/40">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-white">Monitor de citas en tiempo real</h3>
                        <p class="text-sm text-slate-400">Confirmadas, completadas, canceladas y no_show por dia.</p>
                    </div>

                    <div class="flex items-center gap-2" aria-label="Rango de grafica">
                        <button type="button" @click="updateLineRange(7)" :class="rangeBtnClass(7)" class="rounded-lg px-3 py-1.5 text-xs font-semibold" aria-label="Mostrar 7 dias">7 dias</button>
                        <button type="button" @click="updateLineRange(30)" :class="rangeBtnClass(30)" class="rounded-lg px-3 py-1.5 text-xs font-semibold" aria-label="Mostrar 30 dias">30 dias</button>
                        <button type="button" @click="updateLineRange(90)" :class="rangeBtnClass(90)" class="rounded-lg px-3 py-1.5 text-xs font-semibold" aria-label="Mostrar 90 dias">90 dias</button>
                    </div>
                </div>

                <div class="mt-4 h-80">
                    <canvas x-ref="appointmentsChart" aria-label="Grafica de lineas de citas por estado" role="img"></canvas>
                </div>
            </article>
        </section>

        <section aria-label="Panel operativo de aprobaciones, notificaciones y jobs">
            <div class="grid gap-6 xl:grid-cols-3">
                <article class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 shadow-lg shadow-slate-950/40">
                    <h3 class="text-lg font-bold text-white">Negocios pendientes de aprobacion</h3>
                    <p class="text-sm text-slate-400">Negocios en estado pending listos para revision.</p>

                    @if ($pending_businesses->count() > 0)
                        <ul class="mt-4 space-y-3">
                            @foreach ($pending_businesses as $pendingBusiness)
                                <li class="rounded-xl border border-slate-800 bg-slate-950/60 p-3">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-white">{{ $pendingBusiness->nombre }}</p>
                                            <p class="text-xs text-slate-400">{{ strtoupper($pendingBusiness->categoria) }} | {{ optional($pendingBusiness->created_at)->format('d/m/Y H:i') }}</p>
                                        </div>
                                        <span class="rounded-full bg-amber-500/15 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-200">Pending</span>
                                    </div>

                                    @can('platform-admin')
                                        <div class="mt-3 flex items-center gap-2">
                                            <button type="button" @click="openBusinessModal('approve', {{ $pendingBusiness->id }}, @js($pendingBusiness->nombre))" class="rounded-lg bg-emerald-500/15 px-3 py-1.5 text-xs font-semibold text-emerald-200 transition hover:bg-emerald-500/25" aria-label="Aprobar negocio {{ $pendingBusiness->nombre }}">Aprobar</button>
                                            <button type="button" @click="openBusinessModal('suspend', {{ $pendingBusiness->id }}, @js($pendingBusiness->nombre))" class="rounded-lg bg-rose-500/15 px-3 py-1.5 text-xs font-semibold text-rose-200 transition hover:bg-rose-500/25" aria-label="Rechazar negocio {{ $pendingBusiness->nombre }}">Rechazar</button>
                                        </div>
                                    @endcan
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="mt-4 rounded-xl border border-dashed border-slate-700 bg-slate-950/40 p-6 text-center">
                            <svg class="mx-auto h-10 w-10 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            <p class="mt-2 text-sm font-semibold text-slate-200">Sin pendientes por aprobar.</p>
                            <p class="mt-1 text-xs text-slate-400">Todos los negocios estan al dia en este momento.</p>
                        </div>
                    @endif
                </article>

                <article id="notificaciones-log" class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 shadow-lg shadow-slate-950/40">
                    <h3 class="text-lg font-bold text-white">Log de notificaciones recientes</h3>
                    <p class="text-sm text-slate-400">Ultimos 8 envios en notification_logs.</p>

                    @if ($notification_logs->count() > 0)
                        <ul class="mt-4 space-y-3">
                            @foreach ($notification_logs as $notification)
                                @php
                                    $tipo = strtolower($notification->tipo ?? 'email');
                                    $estado = strtolower($notification->estado ?? 'enviado');
                                    $displayUser = trim(implode(' ', array_filter([
                                        $notification->user?->nombre,
                                        $notification->user?->apellidos,
                                    ])));
                                    $displayUser = $displayUser !== '' ? $displayUser : ($notification->user?->email ?? 'Usuario no disponible');
                                @endphp
                                <li class="rounded-xl border border-slate-800 bg-slate-950/60 p-3">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="truncate text-sm font-semibold text-white">{{ ucfirst($notification->evento) }}</p>
                                        <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide {{ $notificationTypeBadge[$tipo] ?? $notificationTypeBadge['email'] }}">{{ $tipo }}</span>
                                    </div>
                                    <p class="mt-1 truncate text-xs text-slate-400">{{ $displayUser }}</p>
                                    <div class="mt-2 flex items-center justify-between text-xs text-slate-400">
                                        <span class="rounded-full px-2 py-0.5 {{ $notificationStatusBadge[$estado] ?? $notificationStatusBadge['enviado'] }}">{{ $estado === 'reintentado' ? 'reintentando' : $estado }}</span>
                                        <span>{{ optional($notification->created_at)->format('d/m/Y H:i') }}</span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="mt-4 rounded-xl border border-dashed border-slate-700 bg-slate-950/40 p-6 text-center">
                            <p class="text-sm font-semibold text-slate-200">Sin registros recientes.</p>
                            <p class="mt-1 text-xs text-slate-400">Las notificaciones apareceran aqui cuando se procesen eventos.</p>
                        </div>
                    @endif
                </article>

                <article id="cola-jobs" class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 shadow-lg shadow-slate-950/40">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-bold text-white">Jobs fallidos</h3>
                            <p class="text-sm text-slate-400">Monitoreo de la cola de procesamiento.</p>
                        </div>

                        <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $failed_jobs_total > 0 ? 'bg-rose-500/20 text-rose-200' : 'bg-emerald-500/20 text-emerald-200' }}">
                            {{ $failed_jobs_total }}
                        </span>
                    </div>

                    @if ($failed_jobs_total > 0)
                        <ul class="mt-4 space-y-3">
                            @foreach ($failed_jobs as $failedJob)
                                <li class="rounded-xl border border-rose-500/20 bg-rose-500/5 p-3">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="truncate text-sm font-semibold text-white">Queue: {{ $failedJob->queue }}</p>
                                        <span class="text-xs text-slate-300">#{{ $failedJob->id }}</span>
                                    </div>
                                    <p class="mt-1 text-xs text-slate-400">{{ \Carbon\Carbon::parse($failedJob->failed_at)->format('d/m/Y H:i') }}</p>

                                    @can('platform-admin')
                                        <form method="POST" action="{{ route('admin.jobs.retry', ['id' => $failedJob->id]) }}" class="mt-2">
                                            @csrf
                                            <button type="submit" class="rounded-lg bg-amber-500/15 px-3 py-1.5 text-xs font-semibold text-amber-200 transition hover:bg-amber-500/25" aria-label="Reintentar job {{ $failedJob->id }}">
                                                Re-intentar
                                            </button>
                                        </form>
                                    @endcan
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="mt-4 rounded-xl border border-dashed border-emerald-500/30 bg-emerald-500/10 p-6 text-center">
                            <svg class="mx-auto h-10 w-10 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            <p class="mt-2 text-sm font-semibold text-emerald-200">Sistema sin jobs fallidos</p>
                            <p class="mt-1 text-xs text-emerald-100/80">La cola de jobs esta saludable.</p>
                        </div>
                    @endif
                </article>
            </div>
        </section>

        <section id="configuracion-rapida" aria-label="Configuracion rapida de plataforma">
            <article class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 shadow-lg shadow-slate-950/40">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-bold text-white">Configuracion rapida de plataforma</h3>
                        <p class="text-sm text-slate-400">Ajustes clave para notificaciones y reglas de validacion.</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <p x-show="settingsSaved" x-transition class="text-xs font-semibold text-emerald-300">Guardado</p>
                        <p x-show="settingsError" x-text="settingsError" x-transition class="text-xs font-semibold text-rose-300"></p>
                        @can('platform-admin')
                            <button type="button" @click="saveSettings" class="rounded-lg bg-indigo-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-indigo-500/30 transition hover:bg-indigo-400" aria-label="Guardar cambios de configuracion">
                                Guardar cambios
                            </button>
                        @endcan
                    </div>
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    @foreach ($settingCards as $settingCard)
                        <div class="rounded-xl border border-slate-800 bg-slate-950/55 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-white">{{ $settingCard['label'] }}</p>
                                    <p class="mt-1 text-xs text-slate-400">{{ $settingCard['description'] }}</p>
                                </div>

                                @can('platform-admin')
                                    <label class="relative inline-flex cursor-pointer items-center" aria-label="Toggle {{ $settingCard['label'] }}">
                                        <input type="checkbox" class="peer sr-only" x-model="settings['{{ $settingCard['key'] }}']" tabindex="0">
                                        <span class="h-6 w-11 rounded-full bg-slate-700 transition peer-checked:bg-indigo-500"></span>
                                        <span class="absolute left-1 h-4 w-4 rounded-full bg-white transition peer-checked:translate-x-5"></span>
                                    </label>
                                @else
                                    <span class="rounded-lg border border-slate-700 px-2 py-1 text-xs text-slate-400">Solo lectura</span>
                                @endcan
                            </div>
                        </div>
                    @endforeach
                </div>
            </article>
        </section>

        @can('platform-admin')
            <div x-show="businessModal.open" x-transition.opacity class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/80 px-4" @keydown.escape.window="closeBusinessModal()">
                <div class="w-full max-w-md rounded-2xl border border-slate-700 bg-slate-900 p-5 shadow-2xl shadow-slate-950/60" @click.outside="closeBusinessModal()">
                    <h4 class="text-lg font-bold text-white" x-text="businessModal.title"></h4>
                    <p class="mt-2 text-sm text-slate-300" x-text="businessModal.message"></p>

                    <form method="POST" :action="businessModal.actionUrl" class="mt-5 flex items-center justify-end gap-2">
                        @csrf
                        <button type="button" @click="closeBusinessModal()" class="rounded-lg border border-slate-600 px-3 py-2 text-sm font-semibold text-slate-200 transition hover:bg-slate-800" aria-label="Cancelar accion">Cancelar</button>
                        <button type="submit" class="rounded-lg px-3 py-2 text-sm font-semibold text-white transition" :class="businessModal.confirmClass" aria-label="Confirmar accion">
                            Confirmar
                        </button>
                    </form>
                </div>
            </div>
        @endcan
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        function adminDashboard(config) {
            return {
                dashboardUrl: config.dashboardUrl,
                settingsUrl: config.settingsUrl,
                approveRouteTemplate: config.approveRouteTemplate,
                suspendRouteTemplate: config.suspendRouteTemplate,
                csrfToken: config.csrfToken,
                selectedEstado: config.selectedEstado && config.selectedEstado !== '' ? config.selectedEstado : 'all',
                businessesLoading: false,
                settings: { ...config.initialSettings },
                settingsSaved: false,
                settingsError: '',
                selectedRange: 30,
                citasData: config.citasData,
                planesData: config.planesData,
                plansChart: null,
                appointmentsChart: null,
                businessModal: {
                    open: false,
                    actionUrl: '',
                    title: '',
                    message: '',
                    confirmClass: 'bg-indigo-500 hover:bg-indigo-400',
                },

                init() {
                    this.initCharts();
                    this.bindBusinessActionButtons();
                    this.bindBusinessesPagination();
                },

                estadoBtnClass(estado) {
                    return this.selectedEstado === estado
                        ? 'bg-indigo-500 text-white shadow-lg shadow-indigo-500/30'
                        : 'bg-slate-800 text-slate-200 hover:bg-slate-700';
                },

                rangeBtnClass(range) {
                    return this.selectedRange === range
                        ? 'bg-indigo-500 text-white shadow-lg shadow-indigo-500/30'
                        : 'bg-slate-800 text-slate-200 hover:bg-slate-700';
                },

                async filterBusinesses(estado) {
                    this.selectedEstado = estado;
                    this.businessesLoading = true;

                    try {
                        const url = new URL(this.dashboardUrl, window.location.origin);
                        if (estado !== 'all') {
                            url.searchParams.set('estado', estado);
                        }
                        url.searchParams.set('partial', 'businesses-table');

                        const response = await fetch(url.toString(), {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        const html = await response.text();
                        this.$refs.businessesTable.innerHTML = html;
                        this.bindBusinessActionButtons();
                        this.bindBusinessesPagination();
                    } finally {
                        this.businessesLoading = false;
                    }
                },

                bindBusinessActionButtons() {
                    const buttons = this.$refs.businessesTable.querySelectorAll('.js-business-action');

                    buttons.forEach((button) => {
                        button.addEventListener('click', () => {
                            const action = button.dataset.action;
                            const businessId = button.dataset.businessId;
                            const businessName = button.dataset.businessName;
                            this.openBusinessModal(action, businessId, businessName);
                        });
                    });
                },

                bindBusinessesPagination() {
                    const paginationLinks = this.$refs.businessesTable.querySelectorAll('.businesses-pagination a');

                    paginationLinks.forEach((link) => {
                        link.addEventListener('click', async (event) => {
                            event.preventDefault();
                            this.businessesLoading = true;

                            try {
                                const url = new URL(link.href);
                                url.searchParams.set('partial', 'businesses-table');

                                if (this.selectedEstado !== 'all') {
                                    url.searchParams.set('estado', this.selectedEstado);
                                }

                                const response = await fetch(url.toString(), {
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest',
                                    },
                                });

                                const html = await response.text();
                                this.$refs.businessesTable.innerHTML = html;
                                this.bindBusinessActionButtons();
                                this.bindBusinessesPagination();
                            } finally {
                                this.businessesLoading = false;
                            }
                        });
                    });
                },

                openBusinessModal(action, id, name) {
                    const businessName = name || 'este negocio';

                    if (action === 'approve') {
                        this.businessModal.actionUrl = this.approveRouteTemplate.replace('__ID__', id);
                        this.businessModal.title = 'Aprobar negocio';
                        this.businessModal.message = `Se aprobara ${businessName} y quedara habilitado en la plataforma.`;
                        this.businessModal.confirmClass = 'bg-emerald-500 hover:bg-emerald-400';
                    } else {
                        this.businessModal.actionUrl = this.suspendRouteTemplate.replace('__ID__', id);
                        this.businessModal.title = 'Suspender negocio';
                        this.businessModal.message = `Se suspendera ${businessName}. Esta accion afecta su operacion en la plataforma.`;
                        this.businessModal.confirmClass = 'bg-rose-500 hover:bg-rose-400';
                    }

                    this.businessModal.open = true;
                },

                closeBusinessModal() {
                    this.businessModal.open = false;
                },

                async saveSettings() {
                    this.settingsError = '';

                    try {
                        const response = await fetch(this.settingsUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({
                                settings: this.settings,
                            }),
                        });

                        if (!response.ok) {
                            throw new Error('No se pudieron guardar los cambios.');
                        }

                        this.settingsSaved = true;
                        window.setTimeout(() => {
                            this.settingsSaved = false;
                        }, 2000);
                    } catch (error) {
                        this.settingsError = error.message;
                        window.setTimeout(() => {
                            this.settingsError = '';
                        }, 2500);
                    }
                },

                initCharts() {
                    this.initPlansChart();
                    this.initAppointmentsChart();
                },

                initPlansChart() {
                    const ctx = this.$refs.planesChart.getContext('2d');
                    const labels = ['Basic', 'Standard', 'Premium'];
                    const values = [
                        this.planesData.basic || 0,
                        this.planesData.standard || 0,
                        this.planesData.premium || 0,
                    ];

                    const centerTextPlugin = {
                        id: 'centerTextPlugin',
                        afterDraw(chart) {
                            const { ctx, chartArea } = chart;
                            if (!chartArea) {
                                return;
                            }

                            const centerX = (chartArea.left + chartArea.right) / 2;
                            const centerY = (chartArea.top + chartArea.bottom) / 2;

                            ctx.save();
                            ctx.fillStyle = '#cbd5e1';
                            ctx.font = '600 12px Inter';
                            ctx.textAlign = 'center';
                            ctx.fillText('Planes', centerX, centerY + 4);
                            ctx.restore();
                        },
                    };

                    const inCanvasLabelsPlugin = {
                        id: 'inCanvasLabelsPlugin',
                        afterDatasetDraw(chart, args) {
                            const { ctx } = chart;
                            const dataset = chart.data.datasets[0];
                            const meta = chart.getDatasetMeta(0);

                            ctx.save();
                            ctx.fillStyle = '#cbd5e1';
                            ctx.font = '600 11px Inter';

                            meta.data.forEach((arc, index) => {
                                const props = arc.getProps(['x', 'y', 'startAngle', 'endAngle', 'outerRadius'], true);
                                const angle = (props.startAngle + props.endAngle) / 2;
                                const radius = props.outerRadius + 14;
                                const x = props.x + Math.cos(angle) * radius;
                                const y = props.y + Math.sin(angle) * radius;
                                const value = dataset.data[index] ?? 0;

                                ctx.textAlign = 'center';
                                ctx.fillText(`${labels[index]} ${value}`, x, y);
                            });

                            ctx.restore();
                        },
                    };

                    this.plansChart = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels,
                            datasets: [{
                                data: values,
                                backgroundColor: ['#6366f1', '#8b5cf6', '#a78bfa'],
                                borderColor: '#0f172a',
                                borderWidth: 2,
                                hoverOffset: 6,
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '65%',
                            plugins: {
                                legend: {
                                    display: false,
                                },
                                tooltip: {
                                    backgroundColor: '#0f172a',
                                    titleColor: '#f8fafc',
                                    bodyColor: '#e2e8f0',
                                },
                            },
                        },
                        plugins: [centerTextPlugin, inCanvasLabelsPlugin],
                    });
                },

                initAppointmentsChart() {
                    const ctx = this.$refs.appointmentsChart.getContext('2d');
                    const filtered = this.getCitasByRange(this.selectedRange);

                    this.appointmentsChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: filtered.labels,
                            datasets: [
                                {
                                    label: 'Confirmed',
                                    data: filtered.confirmed,
                                    borderColor: '#3b82f6',
                                    backgroundColor: 'transparent',
                                    borderWidth: 2,
                                    tension: 0.4,
                                    pointRadius: 3,
                                },
                                {
                                    label: 'Completed',
                                    data: filtered.completed,
                                    borderColor: '#22c55e',
                                    backgroundColor: 'transparent',
                                    borderWidth: 2,
                                    tension: 0.4,
                                    pointRadius: 3,
                                },
                                {
                                    label: 'Cancelled',
                                    data: filtered.cancelled,
                                    borderColor: '#ef4444',
                                    backgroundColor: 'transparent',
                                    borderWidth: 2,
                                    tension: 0.4,
                                    pointRadius: 3,
                                },
                                {
                                    label: 'No Show',
                                    data: filtered.no_show,
                                    borderColor: '#f59e0b',
                                    backgroundColor: 'transparent',
                                    borderWidth: 2,
                                    tension: 0.4,
                                    pointRadius: 3,
                                },
                            ],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                intersect: false,
                                mode: 'index',
                            },
                            plugins: {
                                legend: {
                                    labels: {
                                        color: '#cbd5e1',
                                    },
                                },
                                tooltip: {
                                    backgroundColor: '#0f172a',
                                    titleColor: '#f8fafc',
                                    bodyColor: '#e2e8f0',
                                },
                            },
                            scales: {
                                x: {
                                    ticks: {
                                        color: '#94a3b8',
                                    },
                                    grid: {
                                        color: 'rgba(148, 163, 184, 0.12)',
                                    },
                                },
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        color: '#94a3b8',
                                    },
                                    grid: {
                                        color: 'rgba(148, 163, 184, 0.12)',
                                    },
                                },
                            },
                        },
                    });
                },

                getCitasByRange(range) {
                    const labels = this.citasData.labels || [];
                    const total = labels.length;
                    const take = Math.min(range, total);
                    const start = Math.max(total - take, 0);

                    return {
                        labels: labels.slice(start),
                        confirmed: (this.citasData.confirmed || []).slice(start),
                        completed: (this.citasData.completed || []).slice(start),
                        cancelled: (this.citasData.cancelled || []).slice(start),
                        no_show: (this.citasData.no_show || []).slice(start),
                    };
                },

                updateLineRange(range) {
                    this.selectedRange = range;

                    if (!this.appointmentsChart) {
                        return;
                    }

                    const filtered = this.getCitasByRange(range);
                    this.appointmentsChart.data.labels = filtered.labels;
                    this.appointmentsChart.data.datasets[0].data = filtered.confirmed;
                    this.appointmentsChart.data.datasets[1].data = filtered.completed;
                    this.appointmentsChart.data.datasets[2].data = filtered.cancelled;
                    this.appointmentsChart.data.datasets[3].data = filtered.no_show;
                    this.appointmentsChart.update();
                },
            }
        }
    </script>
@endpush
