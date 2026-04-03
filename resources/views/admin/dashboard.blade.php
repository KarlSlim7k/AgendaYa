@extends('layouts.admin')

@php
    $activeSection = $current_section ?? 'dashboard';
    $sectionTitles = [
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
@endphp

@section('title', $sectionTitles[$activeSection] ?? 'Dashboard')
@section('section_label', 'Plataforma')

@section('content')
    @php
        $kpis = [
            [
                'key'   => 'total_businesses',
                'label' => 'Total de negocios activos',
                'value' => number_format($stats['total_businesses'] ?? 0),
                'icon'  => 'businesses',
            ],
            [
                'key'   => 'citas_hoy',
                'label' => 'Citas hoy',
                'value' => number_format($stats['citas_hoy'] ?? 0),
                'icon'  => 'appointments',
            ],
            [
                'key'   => 'total_users',
                'label' => 'Usuarios registrados',
                'value' => number_format($stats['total_users'] ?? 0),
                'icon'  => 'users',
            ],
            [
                'key'   => 'ingresos_mes',
                'label' => 'Ingresos estimados del mes',
                'value' => '$' . number_format((float) ($stats['ingresos_mes'] ?? 0), 2),
                'icon'  => 'revenue',
            ],
        ];

        $settingCards = [
            ['key' => 'email_notifications_enabled',   'label' => 'Email notifications',  'description' => 'Envia correos automaticos de confirmacion y cambios.'],
            ['key' => 'whatsapp_notifications_enabled','label' => 'WhatsApp notifications','description' => 'Habilita mensajes de WhatsApp para recordatorios.'],
            ['key' => 'require_email_verification',    'label' => 'Verificacion de email', 'description' => 'Solicita email verificado antes de reservar.'],
            ['key' => 'appointment_reminder_24h',      'label' => 'Recordatorio 24h',      'description' => 'Activa recordatorios 24 horas antes de la cita.'],
            ['key' => 'appointment_reminder_1h',       'label' => 'Recordatorio 1h',       'description' => 'Activa recordatorios 1 hora antes de la cita.'],
        ];

        $initialSettings = [];
        foreach ($settingCards as $settingCard) {
            $initialSettings[$settingCard['key']] = (bool) ($platform_settings[$settingCard['key']]->parsed_value ?? false);
        }

        $planesData = [
            'basic'    => (int) ($chart_planes['basic']    ?? 0),
            'standard' => (int) ($chart_planes['standard'] ?? 0),
            'premium'  => (int) ($chart_planes['premium']  ?? 0),
        ];

        $notificationTypeBadge = [
            'email'    => 'bg-blue-500/15 text-blue-200 ring-1 ring-blue-400/30',
            'whatsapp' => 'bg-emerald-500/15 text-emerald-200 ring-1 ring-emerald-400/30',
            'sms'      => 'bg-orange-500/15 text-orange-200 ring-1 ring-orange-400/30',
        ];

        $notificationStatusBadge = [
            'enviado'     => 'bg-emerald-500/15 text-emerald-200 ring-1 ring-emerald-400/30',
            'fallido'     => 'bg-rose-500/15 text-rose-200 ring-1 ring-rose-400/30',
            'reintentado' => 'bg-amber-500/15 text-amber-200 ring-1 ring-amber-400/30',
        ];

        $roleBadgeColors = [
            0 => 'bg-slate-500/20 text-slate-300',
            1 => 'bg-blue-500/20 text-blue-200',
            2 => 'bg-violet-500/20 text-violet-200',
            3 => 'bg-indigo-500/20 text-indigo-200',
            4 => 'bg-amber-500/20 text-amber-200',
        ];

        $healthDriverBadge = fn($v) => match(true) {
            in_array($v, ['redis', 'memcached', 'database']) => 'bg-emerald-500/15 text-emerald-200',
            default => 'bg-slate-700/60 text-slate-300',
        };
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
        class="space-y-6"
    >
        @if (session('status'))
            <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200" role="status">
                {{ session('status') }}
            </div>
        @endif

        {{-- ===================================================================
             SECCION: DASHBOARD (overview general)
        =================================================================== --}}
        @if($activeSection === 'dashboard')
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

            {{-- Accesos rapidos a modulos --}}
            <section aria-label="Accesos rapidos a modulos">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                    @php
                        $quickLinks = [
                            ['href' => route('admin.dashboard', ['seccion' => 'negocios']),       'label' => 'Negocios',        'color' => 'indigo',   'count' => $stats['total_businesses'] ?? 0],
                            ['href' => route('admin.dashboard', ['seccion' => 'usuarios']),       'label' => 'Usuarios',        'color' => 'violet',   'count' => $stats['total_users'] ?? 0],
                            ['href' => route('admin.dashboard', ['seccion' => 'citas']),          'label' => 'Citas',           'color' => 'blue',     'count' => $stats['citas_hoy'] ?? 0],
                            ['href' => route('admin.dashboard', ['seccion' => 'notificaciones']), 'label' => 'Notificaciones',  'color' => 'emerald',  'count' => $topbar_notifications_count ?? 0],
                            ['href' => route('admin.dashboard', ['seccion' => 'jobs']),           'label' => 'Jobs',            'color' => $failed_jobs_total > 0 ? 'rose' : 'slate', 'count' => $failed_jobs_total ?? 0],
                        ];
                    @endphp
                    @foreach($quickLinks as $ql)
                        <a href="{{ $ql['href'] }}" class="group flex flex-col items-center gap-2 rounded-2xl border border-slate-800 bg-slate-900/70 p-4 text-center transition hover:border-{{ $ql['color'] }}-500/40 hover:bg-{{ $ql['color'] }}-500/5 shadow-lg shadow-slate-950/30">
                            <span class="text-2xl font-extrabold text-white">{{ number_format($ql['count']) }}</span>
                            <span class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400 group-hover:text-{{ $ql['color'] }}-300 transition">{{ $ql['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </section>

            <div class="grid gap-6 xl:grid-cols-2">
                {{-- Top negocios --}}
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

                {{-- Negocios pendientes resumen --}}
                <article class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 shadow-lg shadow-slate-950/40">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-bold text-white">Pendientes de aprobacion</h3>
                            <p class="text-sm text-slate-400">Negocios esperando revision.</p>
                        </div>
                        <a href="{{ route('admin.dashboard', ['seccion' => 'negocios']) }}" class="text-xs font-semibold text-indigo-300 hover:text-indigo-200 transition">Ver todos &rarr;</a>
                    </div>

                    @if ($pending_businesses->count() > 0)
                        <ul class="mt-4 space-y-2">
                            @foreach ($pending_businesses->take(4) as $pb)
                                <li class="flex items-center justify-between rounded-xl border border-slate-800 bg-slate-950/50 px-3 py-2">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-white">{{ $pb->nombre }}</p>
                                        <p class="text-xs text-slate-400">{{ strtoupper($pb->categoria ?? '') }} &bull; {{ optional($pb->created_at)->format('d/m/Y') }}</p>
                                    </div>
                                    <span class="rounded-full bg-amber-500/15 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-200">Pending</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="mt-4 rounded-xl border border-dashed border-emerald-500/30 bg-emerald-500/10 p-6 text-center">
                            <svg class="mx-auto h-8 w-8 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            <p class="mt-2 text-sm font-semibold text-emerald-200">Sin pendientes por aprobar.</p>
                        </div>
                    @endif
                </article>
            </div>
        @endif

        {{-- ===================================================================
             SECCION: NEGOCIOS Y TENANTS
        =================================================================== --}}
        @if($activeSection === 'negocios')
            <section id="negocios-tenants" aria-label="Estado de negocios por plan">
                <div class="grid gap-6 xl:grid-cols-2">
                    <article class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 shadow-lg shadow-slate-950/40">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-bold text-white">Negocios por plan y estado</h3>
                                <p class="text-sm text-slate-400">Vista operativa de tenants y volumen de citas del mes actual.</p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2" role="tablist" aria-label="Filtros de estado de negocio">
                                <button type="button" @click="filterBusinesses('all')"       :class="estadoBtnClass('all')"       class="rounded-lg px-3 py-1.5 text-xs font-semibold">Todos</button>
                                <button type="button" @click="filterBusinesses('pending')"   :class="estadoBtnClass('pending')"   class="rounded-lg px-3 py-1.5 text-xs font-semibold">Pending</button>
                                <button type="button" @click="filterBusinesses('approved')"  :class="estadoBtnClass('approved')"  class="rounded-lg px-3 py-1.5 text-xs font-semibold">Approved</button>
                                <button type="button" @click="filterBusinesses('suspended')" :class="estadoBtnClass('suspended')" class="rounded-lg px-3 py-1.5 text-xs font-semibold">Suspended</button>
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

            {{-- Negocios pendientes de aprobacion --}}
            <section aria-label="Negocios pendientes de aprobacion">
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
                                            <p class="text-xs text-slate-400">{{ strtoupper($pendingBusiness->categoria ?? '') }} | {{ optional($pendingBusiness->created_at)->format('d/m/Y H:i') }}</p>
                                        </div>
                                        <span class="rounded-full bg-amber-500/15 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-200">Pending</span>
                                    </div>

                                    @can('platform-admin')
                                        <div class="mt-3 flex items-center gap-2">
                                            <button type="button" @click="openBusinessModal('approve', {{ $pendingBusiness->id }}, @js($pendingBusiness->nombre))" class="rounded-lg bg-emerald-500/15 px-3 py-1.5 text-xs font-semibold text-emerald-200 transition hover:bg-emerald-500/25">Aprobar</button>
                                            <button type="button" @click="openBusinessModal('suspend', {{ $pendingBusiness->id }}, @js($pendingBusiness->nombre))" class="rounded-lg bg-rose-500/15 px-3 py-1.5 text-xs font-semibold text-rose-200 transition hover:bg-rose-500/25">Rechazar</button>
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
            </section>
        @endif

        {{-- ===================================================================
             SECCION: USUARIOS GLOBALES
        =================================================================== --}}
        @if($activeSection === 'usuarios')
            <section id="usuarios-globales" aria-label="Usuarios globales de la plataforma">
                <article class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 shadow-lg shadow-slate-950/40">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-bold text-white">Usuarios de la plataforma</h3>
                            <p class="text-sm text-slate-400">Todos los usuarios registrados (incluyendo eliminados logicamente).</p>
                        </div>
                        <span class="rounded-full bg-violet-500/15 px-3 py-1 text-sm font-bold text-violet-200">
                            {{ number_format($stats['total_users'] ?? 0) }} registrados
                        </span>
                    </div>

                    @if($global_users->count() > 0)
                        <div class="mt-4 overflow-x-auto rounded-xl border border-slate-800">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-slate-800 bg-slate-950/60">
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Usuario</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Email</th>
                                        <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-400 md:table-cell">Telefono</th>
                                        <th class="hidden px-4 py-3 text-center text-xs font-semibold uppercase tracking-[0.12em] text-slate-400 lg:table-cell">Roles asig.</th>
                                        <th class="hidden px-4 py-3 text-center text-xs font-semibold uppercase tracking-[0.12em] text-slate-400 xl:table-cell">Verificado</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Estado</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Registro</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800/70">
                                    @foreach($global_users as $usr)
                                        @php
                                            $usrName = trim(implode(' ', array_filter([$usr->nombre, $usr->apellidos])));
                                            $usrInitials = collect(explode(' ', $usrName))->filter()->take(2)->map(fn($p) => strtoupper(mb_substr($p,0,1)))->implode('');
                                            $isDeleted = !is_null($usr->deleted_at);
                                        @endphp
                                        <tr class="transition hover:bg-slate-800/30 {{ $isDeleted ? 'opacity-50' : '' }}">
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-2.5">
                                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-violet-500/20 text-xs font-bold text-violet-200">
                                                        {{ $usrInitials ?: 'U' }}
                                                    </div>
                                                    <span class="font-medium text-white">{{ $usrName ?: 'Sin nombre' }}</span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-slate-300">{{ $usr->email }}</td>
                                            <td class="hidden px-4 py-3 text-slate-400 md:table-cell">{{ $usr->telefono ?? '—' }}</td>
                                            <td class="hidden px-4 py-3 text-center lg:table-cell">
                                                <span class="rounded-full bg-indigo-500/15 px-2.5 py-0.5 text-xs font-semibold text-indigo-200">{{ $usr->roles_count }}</span>
                                            </td>
                                            <td class="hidden px-4 py-3 text-center xl:table-cell">
                                                @if($usr->email_verified_at)
                                                    <span class="inline-block h-2 w-2 rounded-full bg-emerald-400" title="Verificado"></span>
                                                @else
                                                    <span class="inline-block h-2 w-2 rounded-full bg-slate-600" title="Sin verificar"></span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3">
                                                @if($isDeleted)
                                                    <span class="rounded-full bg-rose-500/15 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-rose-300">Eliminado</span>
                                                @else
                                                    <span class="rounded-full bg-emerald-500/15 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-300">Activo</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-xs text-slate-400">{{ optional($usr->created_at)->format('d/m/Y') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($global_users->hasPages())
                            <div class="mt-4">
                                {{ $global_users->links() }}
                            </div>
                        @endif
                    @else
                        <div class="mt-4 rounded-xl border border-dashed border-slate-700 bg-slate-950/40 p-8 text-center">
                            <svg class="mx-auto h-10 w-10 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
                            <p class="mt-3 text-sm font-semibold text-slate-200">No hay usuarios registrados.</p>
                        </div>
                    @endif
                </article>
            </section>
        @endif

        {{-- ===================================================================
             SECCION: MONITOR DE CITAS
        =================================================================== --}}
        @if($activeSection === 'citas')
            <section id="monitor-citas" aria-label="Monitor de citas en tiempo real">
                <article class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 shadow-lg shadow-slate-950/40">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-bold text-white">Monitor de citas en tiempo real</h3>
                            <p class="text-sm text-slate-400">Confirmadas, completadas, canceladas y no_show por dia.</p>
                        </div>

                        <div class="flex items-center gap-2" aria-label="Rango de grafica">
                            <button type="button" @click="updateLineRange(7)"  :class="rangeBtnClass(7)"  class="rounded-lg px-3 py-1.5 text-xs font-semibold">7 dias</button>
                            <button type="button" @click="updateLineRange(30)" :class="rangeBtnClass(30)" class="rounded-lg px-3 py-1.5 text-xs font-semibold">30 dias</button>
                            <button type="button" @click="updateLineRange(90)" :class="rangeBtnClass(90)" class="rounded-lg px-3 py-1.5 text-xs font-semibold">90 dias</button>
                        </div>
                    </div>

                    <div class="mt-4 h-96">
                        <canvas x-ref="appointmentsChart" aria-label="Grafica de lineas de citas por estado" role="img"></canvas>
                    </div>
                </article>

                <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    @php
                        $citaKpis = [
                            ['label' => 'Citas hoy',        'value' => $stats['citas_hoy'] ?? 0, 'color' => 'indigo'],
                        ];
                    @endphp
                    <article class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4 shadow-lg shadow-slate-950/40">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Citas hoy</p>
                        <p class="mt-1 text-3xl font-extrabold text-white">{{ number_format($stats['citas_hoy'] ?? 0) }}</p>
                    </article>
                    <article class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4 shadow-lg shadow-slate-950/40">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Negocios activos</p>
                        <p class="mt-1 text-3xl font-extrabold text-white">{{ number_format($stats['total_businesses'] ?? 0) }}</p>
                    </article>
                    <article class="col-span-full xl:col-span-2 rounded-2xl border border-slate-800 bg-slate-900/70 p-4 shadow-lg shadow-slate-950/40">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Top negocio del mes</p>
                        @if($top_businesses->first())
                            <p class="mt-1 text-lg font-bold text-white">{{ $top_businesses->first()->nombre }}</p>
                            <p class="text-xs text-slate-400">{{ (int) $top_businesses->first()->citas_completadas }} citas completadas</p>
                        @else
                            <p class="mt-1 text-sm text-slate-400">Sin datos aun</p>
                        @endif
                    </article>
                </div>
            </section>
        @endif

        {{-- ===================================================================
             SECCION: LOG DE NOTIFICACIONES
        =================================================================== --}}
        @if($activeSection === 'notificaciones')
            <section id="notificaciones-log" aria-label="Log de notificaciones">
                <article class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 shadow-lg shadow-slate-950/40">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-bold text-white">Log de notificaciones recientes</h3>
                            <p class="text-sm text-slate-400">Ultimos envios en notification_logs.</p>
                        </div>
                        @if(($topbar_notifications_count ?? 0) > 0)
                            <span class="rounded-full bg-blue-500/20 px-3 py-1 text-xs font-bold text-blue-200">{{ $topbar_notifications_count }} hoy</span>
                        @endif
                    </div>

                    @if ($notification_logs->count() > 0)
                        <div class="mt-4 overflow-x-auto rounded-xl border border-slate-800">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-slate-800 bg-slate-950/60">
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Evento</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Usuario</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Canal</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Estado</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Fecha</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800/70">
                                    @foreach ($notification_logs as $notification)
                                        @php
                                            $tipo   = strtolower($notification->tipo ?? 'email');
                                            $estado = strtolower($notification->estado ?? 'enviado');
                                            $displayUser = trim(implode(' ', array_filter([
                                                $notification->user?->nombre,
                                                $notification->user?->apellidos,
                                            ])));
                                            $displayUser = $displayUser !== '' ? $displayUser : ($notification->user?->email ?? 'Usuario no disponible');
                                        @endphp
                                        <tr class="transition hover:bg-slate-800/30">
                                            <td class="px-4 py-3 font-medium text-white">{{ ucfirst($notification->evento) }}</td>
                                            <td class="px-4 py-3 text-slate-400">{{ $displayUser }}</td>
                                            <td class="px-4 py-3 text-center">
                                                <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide {{ $notificationTypeBadge[$tipo] ?? $notificationTypeBadge['email'] }}">{{ $tipo }}</span>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide {{ $notificationStatusBadge[$estado] ?? $notificationStatusBadge['enviado'] }}">{{ $estado === 'reintentado' ? 'reintentando' : $estado }}</span>
                                            </td>
                                            <td class="px-4 py-3 text-xs text-slate-400">{{ optional($notification->created_at)->format('d/m/Y H:i') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="mt-4 rounded-xl border border-dashed border-slate-700 bg-slate-950/40 p-8 text-center">
                            <p class="text-sm font-semibold text-slate-200">Sin registros recientes.</p>
                            <p class="mt-1 text-xs text-slate-400">Las notificaciones apareceran aqui cuando se procesen eventos.</p>
                        </div>
                    @endif
                </article>
            </section>
        @endif

        {{-- ===================================================================
             SECCION: COLA DE JOBS
        =================================================================== --}}
        @if($activeSection === 'jobs')
            <section id="cola-jobs" aria-label="Cola de jobs y procesamiento">
                <article class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 shadow-lg shadow-slate-950/40">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-bold text-white">Jobs fallidos</h3>
                            <p class="text-sm text-slate-400">Monitoreo de la cola de procesamiento.</p>
                        </div>

                        <span class="rounded-full px-3 py-1 text-sm font-bold {{ $failed_jobs_total > 0 ? 'bg-rose-500/20 text-rose-200' : 'bg-emerald-500/20 text-emerald-200' }}">
                            {{ $failed_jobs_total }} {{ $failed_jobs_total === 1 ? 'job fallido' : 'jobs fallidos' }}
                        </span>
                    </div>

                    @if ($failed_jobs_total > 0)
                        <div class="mt-4 overflow-x-auto rounded-xl border border-rose-500/20">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-rose-500/20 bg-rose-500/5">
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">ID</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Queue</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Fallido en</th>
                                        @can('platform-admin')
                                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Accion</th>
                                        @endcan
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-rose-500/10">
                                    @foreach ($failed_jobs as $failedJob)
                                        <tr class="transition hover:bg-rose-500/5">
                                            <td class="px-4 py-3 font-mono text-xs text-slate-300">#{{ $failedJob->id }}</td>
                                            <td class="px-4 py-3 text-white">{{ $failedJob->queue }}</td>
                                            <td class="px-4 py-3 text-xs text-slate-400">{{ \Carbon\Carbon::parse($failedJob->failed_at)->format('d/m/Y H:i') }}</td>
                                            @can('platform-admin')
                                                <td class="px-4 py-3 text-center">
                                                    <form method="POST" action="{{ route('admin.jobs.retry', ['id' => $failedJob->id]) }}" class="inline">
                                                        @csrf
                                                        <button type="submit" class="rounded-lg bg-amber-500/15 px-3 py-1.5 text-xs font-semibold text-amber-200 transition hover:bg-amber-500/25">Re-intentar</button>
                                                    </form>
                                                </td>
                                            @endcan
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="mt-4 rounded-xl border border-dashed border-emerald-500/30 bg-emerald-500/10 p-8 text-center">
                            <svg class="mx-auto h-10 w-10 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            <p class="mt-2 text-sm font-semibold text-emerald-200">Sistema sin jobs fallidos</p>
                            <p class="mt-1 text-xs text-emerald-100/80">La cola de jobs esta saludable.</p>
                        </div>
                    @endif
                </article>
            </section>
        @endif

        {{-- ===================================================================
             SECCION: CONFIGURACION RAPIDA
        =================================================================== --}}
        @if($activeSection === 'configuracion')
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
                                <button type="button" @click="saveSettings" class="rounded-lg bg-indigo-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-indigo-500/30 transition hover:bg-indigo-400">
                                    Guardar cambios
                                </button>
                            @endcan
                        </div>
                    </div>

                    <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($settingCards as $settingCard)
                            <div class="rounded-xl border border-slate-800 bg-slate-950/55 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-white">{{ $settingCard['label'] }}</p>
                                        <p class="mt-1 text-xs text-slate-400">{{ $settingCard['description'] }}</p>
                                    </div>

                                    @can('platform-admin')
                                        <label class="relative inline-flex cursor-pointer items-center">
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
        @endif

        {{-- ===================================================================
             SECCION: ROLES Y PERMISOS
        =================================================================== --}}
        @if($activeSection === 'roles')
            <section id="roles-permisos" aria-label="Roles y permisos del sistema RBAC">
                <div class="space-y-6">
                    <article class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 shadow-lg shadow-slate-950/40">
                        <h3 class="text-lg font-bold text-white">Jerarquia de roles</h3>
                        <p class="text-sm text-slate-400">Sistema RBAC con 5 niveles jerarquicos. Los roles son globales y se asignan por negocio.</p>

                        @if($roles_list->count() > 0)
                            <div class="mt-4 space-y-3">
                                @foreach($roles_list as $role)
                                    @php
                                        $levelColor = $roleBadgeColors[$role->nivel_jerarquia] ?? 'bg-slate-600/20 text-slate-300';
                                        $barWidth   = ($role->nivel_jerarquia + 1) * 20;
                                    @endphp
                                    <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-4">
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div class="flex items-center gap-3">
                                                <span class="flex h-9 w-9 items-center justify-center rounded-lg {{ $levelColor }} text-sm font-extrabold">
                                                    {{ $role->nivel_jerarquia }}
                                                </span>
                                                <div>
                                                    <p class="font-semibold text-white">{{ $role->display_name ?? $role->nombre }}</p>
                                                    <p class="text-xs text-slate-500 font-mono">{{ $role->nombre }}</p>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <span class="rounded-full bg-indigo-500/15 px-2.5 py-1 text-xs font-semibold text-indigo-200">
                                                    {{ $role->asignaciones_count }} asignaciones
                                                </span>
                                                <span class="rounded-full bg-slate-700/60 px-2.5 py-1 text-xs font-semibold text-slate-300">
                                                    {{ $role->permissions->count() }} permisos
                                                </span>
                                            </div>
                                        </div>

                                        @if($role->descripcion)
                                            <p class="mt-2 text-xs text-slate-400">{{ $role->descripcion }}</p>
                                        @endif

                                        {{-- Nivel bar --}}
                                        <div class="mt-3">
                                            <div class="h-1.5 w-full rounded-full bg-slate-800">
                                                <div class="h-1.5 rounded-full bg-indigo-500" :style="{ width: '{{ $barWidth }}%' }"></div>
                                            </div>
                                        </div>

                                        @if($role->permissions->count() > 0)
                                            <div class="mt-3 flex flex-wrap gap-1.5">
                                                @foreach($role->permissions->take(10) as $perm)
                                                    <span class="rounded-md bg-slate-800 px-2 py-0.5 font-mono text-[10px] text-slate-300">{{ $perm->nombre }}</span>
                                                @endforeach
                                                @if($role->permissions->count() > 10)
                                                    <span class="rounded-md bg-slate-800 px-2 py-0.5 text-[10px] text-slate-500">+{{ $role->permissions->count() - 10 }} mas</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="mt-4 rounded-xl border border-dashed border-slate-700 bg-slate-950/40 p-8 text-center">
                                <svg class="mx-auto h-10 w-10 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                                <p class="mt-3 text-sm font-semibold text-slate-200">No se encontraron roles en el sistema.</p>
                                <p class="mt-1 text-xs text-slate-400">Ejecuta los seeders para inicializar los roles.</p>
                            </div>
                        @endif
                    </article>
                </div>
            </section>
        @endif

        {{-- ===================================================================
             SECCION: REPORTES FINANCIEROS
        =================================================================== --}}
        @if($activeSection === 'finanzas')
            <section id="reportes-financieros" aria-label="Reportes financieros de la plataforma">
                <div class="space-y-6">
                    {{-- KPIs financieros --}}
                    <div class="grid gap-4 sm:grid-cols-3">
                        <article class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 shadow-lg shadow-slate-950/40">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Ingresos del mes</p>
                            <p class="mt-2 text-3xl font-extrabold text-white">${{ number_format((float)($stats['ingresos_mes'] ?? 0), 2) }}</p>
                            @php $ingresosDelta = (float)($stats_deltas['ingresos_mes'] ?? 0); @endphp
                            <div class="mt-3 inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold {{ $ingresosDelta >= 0 ? 'bg-emerald-500/15 text-emerald-200' : 'bg-rose-500/15 text-rose-200' }}">
                                <span>{{ number_format($ingresosDelta, 1) }}% vs mes anterior</span>
                            </div>
                        </article>
                        <article class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 shadow-lg shadow-slate-950/40">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Plan Basic</p>
                            <p class="mt-2 text-3xl font-extrabold text-white">${{ number_format((float)($revenue_by_plan['basic'] ?? 0), 2) }}</p>
                            <p class="mt-3 text-xs text-slate-400">{{ $chart_planes['basic'] ?? 0 }} negocios</p>
                        </article>
                        <article class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 shadow-lg shadow-slate-950/40">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Plan Standard + Premium</p>
                            <p class="mt-2 text-3xl font-extrabold text-white">${{ number_format((float)($revenue_by_plan['standard'] ?? 0) + (float)($revenue_by_plan['premium'] ?? 0), 2) }}</p>
                            <p class="mt-3 text-xs text-slate-400">{{ ($chart_planes['standard'] ?? 0) + ($chart_planes['premium'] ?? 0) }} negocios</p>
                        </article>
                    </div>

                    {{-- Historial mensual --}}
                    <article class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 shadow-lg shadow-slate-950/40">
                        <h3 class="text-lg font-bold text-white">Historial de ingresos mensual</h3>
                        <p class="text-sm text-slate-400">Suma de servicios en citas completadas por mes (ultimos 12 meses).</p>

                        @if($monthly_revenue->count() > 0)
                            <div class="mt-4 overflow-x-auto rounded-xl border border-slate-800">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-slate-800 bg-slate-950/60">
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Periodo</th>
                                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Ingresos</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Barra</th>
                                        </tr>
                                    </thead>
                                    @php $maxRev = $monthly_revenue->max('total') ?: 1; @endphp
                                    <tbody class="divide-y divide-slate-800/70">
                                        @foreach($monthly_revenue as $row)
                                            @php
                                                $monthNames = ['','Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
                                                $label = ($monthNames[(int)$row->month] ?? $row->month) . ' ' . $row->year;
                                                $pct   = round(($row->total / $maxRev) * 100);
                                            @endphp
                                            <tr class="transition hover:bg-slate-800/30">
                                                <td class="px-4 py-3 font-medium text-white">{{ $label }}</td>
                                                <td class="px-4 py-3 text-right font-mono text-emerald-300">${{ number_format((float)$row->total, 2) }}</td>
                                                <td class="px-4 py-3 w-40">
                                                    <div class="h-2 w-full rounded-full bg-slate-800">
                                                        <div class="h-2 rounded-full bg-indigo-500" :style="{ width: '{{ $pct }}%' }"></div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="mt-4 rounded-xl border border-dashed border-slate-700 bg-slate-950/40 p-8 text-center">
                                <svg class="mx-auto h-10 w-10 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" /></svg>
                                <p class="mt-3 text-sm font-semibold text-slate-200">Sin datos financieros aun.</p>
                                <p class="mt-1 text-xs text-slate-400">Los ingresos apareceran cuando se completen citas con servicios asignados.</p>
                            </div>
                        @endif
                    </article>

                    {{-- Distribucion por plan (tabla) --}}
                    <article class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 shadow-lg shadow-slate-950/40">
                        <h3 class="text-lg font-bold text-white">Ingresos por plan (mes actual)</h3>
                        <p class="text-sm text-slate-400">Desglose de ingresos segun el plan de suscripcion del negocio.</p>
                        <div class="mt-4 grid gap-4 sm:grid-cols-3">
                            @foreach(['basic' => ['label'=>'Basic','color'=>'indigo'], 'standard' => ['label'=>'Standard','color'=>'violet'], 'premium' => ['label'=>'Premium','color'=>'amber']] as $planKey => $planMeta)
                                <div class="rounded-xl border border-slate-800 bg-slate-950/55 p-4 text-center">
                                    <span class="rounded-full bg-{{ $planMeta['color'] }}-500/15 px-3 py-1 text-xs font-bold uppercase text-{{ $planMeta['color'] }}-300">{{ $planMeta['label'] }}</span>
                                    <p class="mt-3 text-2xl font-extrabold text-white">${{ number_format((float)($revenue_by_plan[$planKey] ?? 0), 2) }}</p>
                                    <p class="mt-1 text-xs text-slate-400">{{ $chart_planes[$planKey] ?? 0 }} negocios</p>
                                </div>
                            @endforeach
                        </div>
                    </article>
                </div>
            </section>
        @endif

        {{-- ===================================================================
             SECCION: SALUD DEL SISTEMA
        =================================================================== --}}
        @if($activeSection === 'salud')
            <section id="salud-sistema" aria-label="Salud del sistema">
                <div class="space-y-6">
                    {{-- Estado general --}}
                    <article class="rounded-2xl border {{ ($system_health['is_healthy'] ?? true) ? 'border-emerald-500/30 bg-emerald-500/5' : 'border-rose-500/30 bg-rose-500/5' }} p-5 shadow-lg shadow-slate-950/40">
                        <div class="flex items-center gap-4">
                            @if($system_health['is_healthy'] ?? true)
                                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-500/20 text-emerald-400">
                                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                </div>
                                <div>
                                    <p class="text-lg font-bold text-emerald-200">Sistema operativo</p>
                                    <p class="text-sm text-emerald-400/80">Todos los servicios funcionan correctamente.</p>
                                </div>
                            @else
                                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-rose-500/20 text-rose-400">
                                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                                </div>
                                <div>
                                    <p class="text-lg font-bold text-rose-200">Atencion requerida</p>
                                    <p class="text-sm text-rose-400/80">Hay {{ $system_health['failed_jobs'] ?? 0 }} jobs fallidos en la cola.</p>
                                </div>
                            @endif
                        </div>
                    </article>

                    {{-- Indicadores del sistema --}}
                    <article class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 shadow-lg shadow-slate-950/40">
                        <h3 class="text-lg font-bold text-white">Informacion del entorno</h3>
                        <p class="text-sm text-slate-400">Variables de entorno y versiones del runtime.</p>

                        @if(!empty($system_health))
                            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                @php
                                    $healthItems = [
                                        ['label' => 'PHP Version',      'value' => $system_health['php_version']      ?? '—', 'icon' => 'code'],
                                        ['label' => 'Laravel Version',  'value' => $system_health['laravel_version']  ?? '—', 'icon' => 'flame'],
                                        ['label' => 'Entorno',          'value' => strtoupper($system_health['environment'] ?? '—'), 'icon' => 'globe'],
                                        ['label' => 'Version App',      'value' => $system_health['app_version']      ?? '—', 'icon' => 'tag'],
                                        ['label' => 'Timezone',         'value' => $system_health['timezone']         ?? '—', 'icon' => 'clock'],
                                        ['label' => 'DB Driver',        'value' => strtoupper($system_health['db_connection'] ?? '—'), 'icon' => 'database'],
                                        ['label' => 'Cache Driver',     'value' => strtoupper($system_health['cache_driver']  ?? '—'), 'icon' => 'bolt'],
                                        ['label' => 'Queue Driver',     'value' => strtoupper($system_health['queue_driver']  ?? '—'), 'icon' => 'queue'],
                                        ['label' => 'Jobs Fallidos',    'value' => (string)($system_health['failed_jobs'] ?? 0), 'icon' => 'warning', 'alert' => ($system_health['failed_jobs'] ?? 0) > 0],
                                    ];
                                @endphp

                                @foreach($healthItems as $item)
                                    <div class="rounded-xl border {{ !empty($item['alert']) ? 'border-rose-500/30 bg-rose-500/5' : 'border-slate-800 bg-slate-950/55' }} p-4">
                                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">{{ $item['label'] }}</p>
                                        <p class="mt-1.5 font-mono text-sm font-semibold {{ !empty($item['alert']) ? 'text-rose-300' : 'text-white' }}">{{ $item['value'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="mt-4 rounded-xl border border-dashed border-slate-700 bg-slate-950/40 p-8 text-center">
                                <p class="text-sm font-semibold text-slate-200">No se pudo obtener informacion del sistema.</p>
                            </div>
                        @endif
                    </article>

                    {{-- Base de datos --}}
                    <article class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 shadow-lg shadow-slate-950/40">
                        <h3 class="text-lg font-bold text-white">Estado de la base de datos</h3>
                        <p class="text-sm text-slate-400">Conectividad y tablas principales del sistema.</p>

                        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            @php
                                $tables = ['users', 'businesses', 'appointments', 'services', 'employees', 'roles', 'permissions', 'notification_logs', 'failed_jobs', 'platform_settings'];
                            @endphp
                            @foreach($tables as $table)
                                @php $exists = \Illuminate\Support\Facades\Schema::hasTable($table); @endphp
                                <div class="flex items-center gap-3 rounded-xl border {{ $exists ? 'border-emerald-500/20 bg-emerald-500/5' : 'border-slate-800 bg-slate-950/40' }} px-4 py-3">
                                    <span class="h-2 w-2 rounded-full {{ $exists ? 'bg-emerald-400' : 'bg-slate-600' }}"></span>
                                    <span class="font-mono text-xs {{ $exists ? 'text-emerald-200' : 'text-slate-500' }}">{{ $table }}</span>
                                </div>
                            @endforeach
                        </div>
                    </article>

                    @if(($system_health['failed_jobs'] ?? 0) > 0)
                        <article class="rounded-2xl border border-rose-500/20 bg-rose-500/5 p-5 shadow-lg shadow-slate-950/40">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <h3 class="text-lg font-bold text-rose-200">Jobs fallidos detectados</h3>
                                    <p class="text-sm text-rose-400/80">Hay {{ $system_health['failed_jobs'] }} jobs en la cola de fallos.</p>
                                </div>
                                <a href="{{ route('admin.dashboard', ['seccion' => 'jobs']) }}" class="rounded-lg bg-rose-500/15 px-4 py-2 text-sm font-semibold text-rose-200 transition hover:bg-rose-500/25">
                                    Ver jobs &rarr;
                                </a>
                            </div>
                        </article>
                    @endif
                </div>
            </section>
        @endif

        {{-- Modal de confirmacion de negocios (disponible en negocios y dashboard) --}}
        @if(in_array($activeSection, ['negocios', 'dashboard']))
            @can('platform-admin')
                <div x-show="businessModal.open" x-transition.opacity class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/80 px-4" @keydown.escape.window="closeBusinessModal()">
                    <div class="w-full max-w-md rounded-2xl border border-slate-700 bg-slate-900 p-5 shadow-2xl shadow-slate-950/60" @click.outside="closeBusinessModal()">
                        <h4 class="text-lg font-bold text-white" x-text="businessModal.title"></h4>
                        <p class="mt-2 text-sm text-slate-300" x-text="businessModal.message"></p>

                        <form method="POST" :action="businessModal.actionUrl" class="mt-5 flex items-center justify-end gap-2">
                            @csrf
                            <button type="button" @click="closeBusinessModal()" class="rounded-lg border border-slate-600 px-3 py-2 text-sm font-semibold text-slate-200 transition hover:bg-slate-800">Cancelar</button>
                            <button type="submit" class="rounded-lg px-3 py-2 text-sm font-semibold text-white transition" :class="businessModal.confirmClass">
                                Confirmar
                            </button>
                        </form>
                    </div>
                </div>
            @endcan
        @endif
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
                        url.searchParams.set('seccion', 'negocios');
                        url.searchParams.set('partial', 'businesses-table');

                        const response = await fetch(url.toString(), {
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
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
                    if (!this.$refs.businessesTable) return;

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
                    if (!this.$refs.businessesTable) return;

                    const paginationLinks = this.$refs.businessesTable.querySelectorAll('.businesses-pagination a');
                    paginationLinks.forEach((link) => {
                        link.addEventListener('click', async (event) => {
                            event.preventDefault();
                            this.businessesLoading = true;

                            try {
                                const url = new URL(link.href);
                                url.searchParams.set('seccion', 'negocios');
                                url.searchParams.set('partial', 'businesses-table');

                                if (this.selectedEstado !== 'all') {
                                    url.searchParams.set('estado', this.selectedEstado);
                                }

                                const response = await fetch(url.toString(), {
                                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
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
                            body: JSON.stringify({ settings: this.settings }),
                        });

                        if (!response.ok) {
                            throw new Error('No se pudieron guardar los cambios.');
                        }

                        this.settingsSaved = true;
                        window.setTimeout(() => { this.settingsSaved = false; }, 2000);
                    } catch (error) {
                        this.settingsError = error.message;
                        window.setTimeout(() => { this.settingsError = ''; }, 2500);
                    }
                },

                initCharts() {
                    this.initPlansChart();
                    this.initAppointmentsChart();
                },

                initPlansChart() {
                    if (!this.$refs.planesChart) return;
                    if (this.plansChart) { this.plansChart.destroy(); }

                    const ctx = this.$refs.planesChart.getContext('2d');
                    const labels = ['Basic', 'Standard', 'Premium'];
                    const values = [
                        this.planesData.basic    || 0,
                        this.planesData.standard || 0,
                        this.planesData.premium  || 0,
                    ];

                    const centerTextPlugin = {
                        id: 'centerTextPlugin',
                        afterDraw(chart) {
                            const { ctx, chartArea } = chart;
                            if (!chartArea) return;
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
                                legend: { display: false },
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
                    if (!this.$refs.appointmentsChart) return;
                    if (this.appointmentsChart) { this.appointmentsChart.destroy(); }

                    const ctx = this.$refs.appointmentsChart.getContext('2d');
                    const filtered = this.getCitasByRange(this.selectedRange);

                    this.appointmentsChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: filtered.labels,
                            datasets: [
                                { label: 'Confirmed', data: filtered.confirmed, borderColor: '#3b82f6', backgroundColor: 'transparent', borderWidth: 2, tension: 0.4, pointRadius: 3 },
                                { label: 'Completed', data: filtered.completed, borderColor: '#22c55e', backgroundColor: 'transparent', borderWidth: 2, tension: 0.4, pointRadius: 3 },
                                { label: 'Cancelled', data: filtered.cancelled, borderColor: '#ef4444', backgroundColor: 'transparent', borderWidth: 2, tension: 0.4, pointRadius: 3 },
                                { label: 'No Show',   data: filtered.no_show,   borderColor: '#f59e0b', backgroundColor: 'transparent', borderWidth: 2, tension: 0.4, pointRadius: 3 },
                            ],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: { intersect: false, mode: 'index' },
                            plugins: {
                                legend: { labels: { color: '#cbd5e1' } },
                                tooltip: { backgroundColor: '#0f172a', titleColor: '#f8fafc', bodyColor: '#e2e8f0' },
                            },
                            scales: {
                                x: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(148, 163, 184, 0.12)' } },
                                y: { beginAtZero: true, ticks: { color: '#94a3b8' }, grid: { color: 'rgba(148, 163, 184, 0.12)' } },
                            },
                        },
                    });
                },

                getCitasByRange(range) {
                    const labels = this.citasData.labels || [];
                    const total  = labels.length;
                    const take   = Math.min(range, total);
                    const start  = Math.max(total - take, 0);

                    return {
                        labels:    labels.slice(start),
                        confirmed: (this.citasData.confirmed || []).slice(start),
                        completed: (this.citasData.completed || []).slice(start),
                        cancelled: (this.citasData.cancelled || []).slice(start),
                        no_show:   (this.citasData.no_show   || []).slice(start),
                    };
                },

                updateLineRange(range) {
                    this.selectedRange = range;
                    if (!this.appointmentsChart) return;
                    const filtered = this.getCitasByRange(range);
                    this.appointmentsChart.data.labels          = filtered.labels;
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
