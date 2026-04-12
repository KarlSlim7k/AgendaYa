@extends('layouts.business')

@section('title', 'Dashboard')
@section('section_label', 'Mi Negocio')
@section('content')

@php
    $periods = [
        'today' => 'Hoy',
        'week' => 'Semana',
        'month' => 'Mes',
        'year' => 'Año',
    ];

    $statusColors = [
        'pending'   => ['bg' => 'bg-amber-500/15',  'text' => 'text-amber-200',  'ring' => 'ring-amber-500/40',  'dot' => 'bg-amber-400',  'bar' => 'bg-amber-400',  'label' => 'Pendientes'],
        'confirmed' => ['bg' => 'bg-blue-500/15',   'text' => 'text-blue-200',   'ring' => 'ring-blue-500/40',   'dot' => 'bg-blue-400',   'bar' => 'bg-blue-400',   'label' => 'Confirmadas'],
        'completed' => ['bg' => 'bg-emerald-500/15','text' => 'text-emerald-200','ring' => 'ring-emerald-500/40','dot' => 'bg-emerald-400','bar' => 'bg-emerald-400','label' => 'Completadas'],
        'cancelled' => ['bg' => 'bg-rose-500/15',   'text' => 'text-rose-200',   'ring' => 'ring-rose-500/40',   'dot' => 'bg-rose-400',   'bar' => 'bg-rose-400',   'label' => 'Canceladas'],
    ];

    $totalStatusCount = array_sum($appointmentsByStatus ?? []) ?: 1;
@endphp

<div class="space-y-6">

    {{-- Period Selector --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-bold text-white">Resumen general</h2>
            <p class="text-sm text-slate-400">Mostrando datos de: <span class="font-semibold text-emerald-300">{{ $periods[$selectedPeriod] }}</span></p>
        </div>
        <div class="flex gap-1.5 rounded-xl border border-slate-800 bg-slate-950/60 p-1.5">
            @foreach($periods as $key => $label)
                <a href="{{ route('business.dashboard', ['periodo' => $key]) }}"
                   class="rounded-lg px-4 py-1.5 text-sm font-medium transition-all duration-200 {{ $selectedPeriod === $key ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/25' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- KPI Grid --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">

        {{-- Total Citas --}}
        <div class="group relative overflow-hidden rounded-xl border border-slate-800 bg-gradient-to-br from-slate-900 to-slate-950 p-6 transition hover:border-indigo-500/40">
            <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/5 to-transparent opacity-0 transition group-hover:opacity-100"></div>
            <div class="relative flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">Total Citas</p>
                    <p class="mt-2 text-4xl font-extrabold tracking-tight text-white">{{ $totalAppointments }}</p>
                </div>
                <div class="rounded-xl bg-indigo-500/15 p-3 ring-1 ring-indigo-500/30">
                    <svg class="h-6 w-6 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Confirmadas --}}
        <div class="group relative overflow-hidden rounded-xl border border-slate-800 bg-gradient-to-br from-slate-900 to-slate-950 p-6 transition hover:border-blue-500/40">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-transparent opacity-0 transition group-hover:opacity-100"></div>
            <div class="relative flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">Confirmadas</p>
                    <p class="mt-2 text-4xl font-extrabold tracking-tight text-blue-300">{{ $confirmedAppointments }}</p>
                </div>
                <div class="rounded-xl bg-blue-500/15 p-3 ring-1 ring-blue-500/30">
                    <svg class="h-6 w-6 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Completadas --}}
        <div class="group relative overflow-hidden rounded-xl border border-slate-800 bg-gradient-to-br from-slate-900 to-slate-950 p-6 transition hover:border-emerald-500/40">
            <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 to-transparent opacity-0 transition group-hover:opacity-100"></div>
            <div class="relative flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">Completadas</p>
                    <p class="mt-2 text-4xl font-extrabold tracking-tight text-emerald-300">{{ $completedAppointments }}</p>
                </div>
                <div class="rounded-xl bg-emerald-500/15 p-3 ring-1 ring-emerald-500/30">
                    <svg class="h-6 w-6 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Ingresos --}}
        <div class="group relative overflow-hidden rounded-xl border border-slate-800 bg-gradient-to-br from-slate-900 to-slate-950 p-6 transition hover:border-emerald-500/40">
            <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 to-transparent opacity-0 transition group-hover:opacity-100"></div>
            <div class="relative flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">Ingresos</p>
                    <p class="mt-2 text-4xl font-extrabold tracking-tight text-emerald-300">${{ number_format($revenue, 0) }}</p>
                    <p class="mt-1 text-xs text-slate-500">MXN</p>
                </div>
                <div class="rounded-xl bg-emerald-500/15 p-3 ring-1 ring-emerald-500/30">
                    <svg class="h-6 w-6 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Middle Row --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">

        {{-- Estado de Citas --}}
        <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-6">
            <h3 class="mb-5 text-sm font-semibold uppercase tracking-widest text-slate-400">Estado de Citas</h3>
            <div class="space-y-4">
                @foreach($appointmentsByStatus as $status => $count)
                    @php
                        $colors = $statusColors[$status] ?? ['bg' => 'bg-slate-600/20','text' => 'text-slate-300','dot' => 'bg-slate-400','bar' => 'bg-slate-500','label' => $status];
                        $pct = round(($count / $totalStatusCount) * 100);
                    @endphp
                    <div>
                        <div class="mb-1.5 flex items-center justify-between text-sm">
                            <div class="flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full {{ $colors['dot'] }}"></span>
                                <span class="text-slate-300">{{ $colors['label'] }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-white">{{ $count }}</span>
                                <span class="text-xs text-slate-500">{{ $pct }}%</span>
                            </div>
                        </div>
                        <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-800">
                            <div class="h-1.5 rounded-full {{ $colors['bar'] }} transition-all duration-500" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Resumen del Negocio --}}
        <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-6">
            <h3 class="mb-5 text-sm font-semibold uppercase tracking-widest text-slate-400">Resumen del Negocio</h3>
            <div class="space-y-3">
                @foreach([
                    ['Clientes unicos',    $uniqueClients,  'text-white'],
                    ['Servicios activos',  $totalServices,  'text-white'],
                    ['Empleados activos',  $totalEmployees, 'text-white'],
                    ['Sucursales activas', $totalLocations, 'text-white'],
                ] as [$label, $value, $color])
                    <div class="flex items-center justify-between rounded-lg bg-slate-800/40 px-4 py-3">
                        <span class="text-sm text-slate-400">{{ $label }}</span>
                        <span class="text-lg font-bold {{ $color }}">{{ $value }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Acciones Rapidas --}}
        <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-6">
            <h3 class="mb-5 text-sm font-semibold uppercase tracking-widest text-slate-400">Acciones Rapidas</h3>
            <div class="space-y-2">
                <a href="{{ route('business.appointments.create') }}"
                   class="flex items-center gap-3 rounded-xl border border-slate-700/60 bg-slate-800/40 px-4 py-3 text-sm font-medium text-slate-200 transition hover:border-emerald-500/50 hover:bg-emerald-500/10 hover:text-emerald-200">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-500/15 text-emerald-300">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    Nueva Cita
                </a>
                <a href="{{ route('business.services.create') }}"
                   class="flex items-center gap-3 rounded-xl border border-slate-700/60 bg-slate-800/40 px-4 py-3 text-sm font-medium text-slate-200 transition hover:border-blue-500/50 hover:bg-blue-500/10 hover:text-blue-200">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-500/15 text-blue-300">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                        </svg>
                    </div>
                    Nuevo Servicio
                </a>
                <a href="{{ route('business.employees.create') }}"
                   class="flex items-center gap-3 rounded-xl border border-slate-700/60 bg-slate-800/40 px-4 py-3 text-sm font-medium text-slate-200 transition hover:border-violet-500/50 hover:bg-violet-500/10 hover:text-violet-200">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-violet-500/15 text-violet-300">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                    </div>
                    Nuevo Empleado
                </a>
                <a href="{{ route('business.reports.index') }}"
                   class="flex items-center gap-3 rounded-xl border border-slate-700/60 bg-slate-800/40 px-4 py-3 text-sm font-medium text-slate-200 transition hover:border-amber-500/50 hover:bg-amber-500/10 hover:text-amber-200">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-500/15 text-amber-300">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    Ver Reportes
                </a>
            </div>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">

        {{-- Servicios Mas Solicitados --}}
        <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-6">
            <h3 class="mb-5 text-sm font-semibold uppercase tracking-widest text-slate-400">Servicios Mas Solicitados</h3>
            @if(count($topServices) > 0)
                @php $maxService = max(array_column($topServices, 'total')); @endphp
                <div class="space-y-4">
                    @foreach($topServices as $i => $service)
                        <div>
                            <div class="mb-1.5 flex items-center justify-between text-sm">
                                <div class="flex items-center gap-2">
                                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-500/20 text-[10px] font-bold text-emerald-300">{{ $i + 1 }}</span>
                                    <span class="text-slate-300">{{ $service['nombre'] }}</span>
                                </div>
                                <span class="font-bold text-white">{{ $service['total'] }}</span>
                            </div>
                            <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-800">
                                <div class="h-1.5 rounded-full bg-gradient-to-r from-emerald-600 to-emerald-400 transition-all duration-500"
                                     style="width: {{ ($service['total'] / $maxService) * 100 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-10 text-center">
                    <div class="rounded-full bg-slate-800 p-4">
                        <svg class="h-8 w-8 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                        </svg>
                    </div>
                    <p class="mt-3 text-sm text-slate-500">Sin datos disponibles</p>
                </div>
            @endif
        </div>

        {{-- Rendimiento por Empleado --}}
        <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-6">
            <h3 class="mb-5 text-sm font-semibold uppercase tracking-widest text-slate-400">Rendimiento por Empleado</h3>
            @if(count($employeePerformance) > 0)
                @php $maxEmp = max(array_column($employeePerformance, 'total')); @endphp
                <div class="space-y-4">
                    @foreach($employeePerformance as $i => $emp)
                        <div>
                            <div class="mb-1.5 flex items-center justify-between text-sm">
                                <div class="flex items-center gap-2">
                                    <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-500/20 text-[10px] font-bold text-blue-300">
                                        {{ strtoupper(substr($emp['nombre'], 0, 1)) }}
                                    </div>
                                    <span class="text-slate-300">{{ $emp['nombre'] }}</span>
                                </div>
                                <span class="font-bold text-white">{{ $emp['total'] }}</span>
                            </div>
                            <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-800">
                                <div class="h-1.5 rounded-full bg-gradient-to-r from-blue-600 to-blue-400 transition-all duration-500"
                                     style="width: {{ ($emp['total'] / $maxEmp) * 100 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-10 text-center">
                    <div class="rounded-full bg-slate-800 p-4">
                        <svg class="h-8 w-8 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <p class="mt-3 text-sm text-slate-500">Sin datos disponibles</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Today's Appointments --}}
    <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900/60">
        <div class="flex items-center justify-between border-b border-slate-800 px-6 py-4">
            <div>
                <h3 class="font-bold text-white">Citas de Hoy</h3>
                <p class="text-xs text-slate-500">{{ \Carbon\Carbon::today()->isoFormat('dddd, D [de] MMMM') }}</p>
            </div>
            @if(count($todayAppointments) > 0)
                <span class="rounded-full bg-emerald-500/15 px-2.5 py-0.5 text-xs font-bold text-emerald-300">
                    {{ count($todayAppointments) }} citas
                </span>
            @endif
        </div>
        <div class="divide-y divide-slate-800/60">
            @forelse($todayAppointments as $appointment)
                @php
                    $statusLabel = match($appointment->estado) {
                        'pending'   => 'Pendiente',
                        'confirmed' => 'Confirmada',
                        'completed' => 'Completada',
                        'cancelled' => 'Cancelada',
                        default     => $appointment->estado,
                    };
                    $statusClass = match($appointment->estado) {
                        'pending'   => 'bg-amber-500/15 text-amber-200 ring-1 ring-amber-500/30',
                        'confirmed' => 'bg-blue-500/15 text-blue-200 ring-1 ring-blue-500/30',
                        'completed' => 'bg-emerald-500/15 text-emerald-200 ring-1 ring-emerald-500/30',
                        'cancelled' => 'bg-rose-500/15 text-rose-200 ring-1 ring-rose-500/30',
                        default     => 'bg-slate-600/20 text-slate-300',
                    };
                    $initial1 = substr($appointment->user?->nombre ?? 'U', 0, 1);
                    $initial2 = substr($appointment->user?->apellidos ?? '', 0, 1);
                @endphp
                <div class="group flex items-center justify-between gap-4 px-6 py-4 transition hover:bg-slate-800/30">
                    <div class="flex items-center gap-4">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-emerald-500/20 to-emerald-600/10 text-sm font-bold text-emerald-300 ring-1 ring-emerald-500/20">
                            {{ strtoupper($initial1 . $initial2) }}
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-white">
                                {{ $appointment->user?->nombre }} {{ $appointment->user?->apellidos }}
                            </p>
                            <p class="text-xs text-slate-400">{{ $appointment->service?->nombre }}</p>
                            @if($appointment->employee)
                                <p class="text-xs text-slate-500">con {{ $appointment->employee->nombre }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="flex shrink-0 flex-col items-end gap-1.5">
                        <p class="text-sm font-bold text-white">
                            {{ \Carbon\Carbon::parse($appointment->fecha_hora_inicio)->format('H:i') }}
                        </p>
                        <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold {{ $statusClass }}">
                            {{ $statusLabel }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center px-6 py-14 text-center">
                    <div class="rounded-full bg-slate-800/60 p-5">
                        <svg class="h-10 w-10 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <p class="mt-4 text-sm font-semibold text-slate-300">Sin citas para hoy</p>
                    <p class="mt-1 text-xs text-slate-500">Las citas de hoy apareceran aqui.</p>
                    <a href="{{ route('business.appointments.create') }}"
                       class="mt-4 rounded-lg bg-emerald-600/20 px-4 py-2 text-xs font-semibold text-emerald-300 transition hover:bg-emerald-600/30">
                        Crear nueva cita
                    </a>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Upcoming Appointments --}}
    <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900/60">
        <div class="flex items-center justify-between border-b border-slate-800 px-6 py-4">
            <h3 class="font-bold text-white">Proximas Citas</h3>
            <a href="{{ route('business.appointments.index') }}"
               class="flex items-center gap-1 text-xs font-semibold text-emerald-400 transition hover:text-emerald-300">
                Ver todas
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
        <div class="divide-y divide-slate-800/60">
            @forelse($upcomingAppointments as $appointment)
                @php
                    $initial1 = substr($appointment['user']['nombre'] ?? 'U', 0, 1);
                    $initial2 = substr($appointment['user']['apellidos'] ?? '', 0, 1);
                    $fecha = \Carbon\Carbon::parse($appointment['fecha_hora_inicio']);
                    $esHoy = $fecha->isToday();
                    $esMañana = $fecha->isTomorrow();
                    $etiqueta = $esHoy ? 'Hoy' : ($esMañana ? 'Mañana' : $fecha->isoFormat('D MMM'));
                @endphp
                <div class="flex items-center justify-between gap-4 px-6 py-4 transition hover:bg-slate-800/30">
                    <div class="flex items-center gap-4">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-blue-500/20 to-blue-600/10 text-sm font-bold text-blue-300 ring-1 ring-blue-500/20">
                            {{ strtoupper($initial1 . $initial2) }}
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-white">
                                {{ $appointment['user']['nombre'] }} {{ $appointment['user']['apellidos'] }}
                            </p>
                            <p class="text-xs text-slate-400">{{ $appointment['service']['nombre'] }}</p>
                            @if(!empty($appointment['employee']))
                                <p class="text-xs text-slate-500">con {{ $appointment['employee']['nombre'] }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="flex shrink-0 flex-col items-end gap-1">
                        <span class="text-xs font-semibold text-slate-300">{{ $etiqueta }}</span>
                        <span class="text-sm font-bold text-white">{{ $fecha->format('H:i') }}</span>
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center px-6 py-14 text-center">
                    <div class="rounded-full bg-slate-800/60 p-5">
                        <svg class="h-10 w-10 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="mt-4 text-sm font-semibold text-slate-300">Sin citas proximas</p>
                    <p class="mt-1 text-xs text-slate-500">Las citas confirmadas apareceran aqui.</p>
                </div>
            @endforelse
        </div>
    </div>

</div>
@endsection
