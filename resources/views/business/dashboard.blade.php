@extends('layouts.business')

@section('title', 'Dashboard')
@section('section_label', 'Mi Negocio')

@php
    $periods = [
        'today' => 'Hoy',
        'week' => 'Semana',
        'month' => 'Mes',
        'year' => 'Año',
    ];

    $statusColors = [
        'pending' => ['bg' => 'bg-amber-500/15', 'text' => 'text-amber-200', 'ring' => 'ring-amber-500/40', 'dot' => 'bg-amber-400', 'label' => 'Pendientes'],
        'confirmed' => ['bg' => 'bg-blue-500/15', 'text' => 'text-blue-200', 'ring' => 'ring-blue-500/40', 'dot' => 'bg-blue-400', 'label' => 'Confirmadas'],
        'completed' => ['bg' => 'bg-emerald-500/15', 'text' => 'text-emerald-200', 'ring' => 'ring-emerald-500/40', 'dot' => 'bg-emerald-400', 'label' => 'Completadas'],
        'cancelled' => ['bg' => 'bg-rose-500/15', 'text' => 'text-rose-200', 'ring' => 'ring-rose-500/40', 'dot' => 'bg-rose-400', 'label' => 'Canceladas'],
    ];
@endphp

<div class="space-y-6">
    <!-- Period Selector -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-slate-400">Resumen del periodo: <span class="font-semibold text-slate-200">{{ $periods[$selectedPeriod] }}</span></p>
        </div>
        <div class="flex gap-2">
            @foreach($periods as $key => $label)
                <a href="{{ route('business.dashboard', ['periodo' => $key]) }}"
                   class="rounded-lg px-4 py-2 text-sm font-medium transition {{ $selectedPeriod === $key ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/20' : 'bg-slate-800 text-slate-300 hover:bg-slate-700 hover:text-white' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    <!-- KPIs Grid -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Total Citas -->
        <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-400">Total Citas</p>
                    <p class="mt-1 text-3xl font-bold text-white">{{ $totalAppointments }}</p>
                </div>
                <div class="rounded-full bg-indigo-500/15 p-3">
                    <svg class="h-8 w-8 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Confirmadas -->
        <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-400">Confirmadas</p>
                    <p class="mt-1 text-3xl font-bold text-blue-300">{{ $confirmedAppointments }}</p>
                </div>
                <div class="rounded-full bg-blue-500/15 p-3">
                    <svg class="h-8 w-8 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Completadas -->
        <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-400">Completadas</p>
                    <p class="mt-1 text-3xl font-bold text-emerald-300">{{ $completedAppointments }}</p>
                </div>
                <div class="rounded-full bg-emerald-500/15 p-3">
                    <svg class="h-8 w-8 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Ingresos -->
        <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-400">Ingresos</p>
                    <p class="mt-1 text-3xl font-bold text-emerald-300">${{ number_format($revenue, 2) }}</p>
                </div>
                <div class="rounded-full bg-emerald-500/15 p-3">
                    <svg class="h-8 w-8 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Breakdown + Quick Stats -->
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <!-- Status Breakdown -->
        <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-6">
            <h3 class="mb-4 text-lg font-bold text-white">Estado de Citas</h3>
            <div class="space-y-3">
                @foreach($appointmentsByStatus as $status => $count)
                    @php $colors = $statusColors[$status] ?? ['bg' => 'bg-slate-600/20', 'text' => 'text-slate-300', 'ring' => 'ring-slate-500/40', 'dot' => 'bg-slate-400', 'label' => $status]; @endphp
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full {{ $colors['dot'] }}"></span>
                            <span class="text-sm text-slate-300">{{ $colors['label'] }}</span>
                        </div>
                        <span class="rounded-full {{ $colors['bg'] }} px-2.5 py-0.5 text-sm font-semibold {{ $colors['text'] }}">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-6">
            <h3 class="mb-4 text-lg font-bold text-white">Resumen del Negocio</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-400">Clientes unicos</span>
                    <span class="text-lg font-bold text-white">{{ $uniqueClients }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-400">Servicios activos</span>
                    <span class="text-lg font-bold text-white">{{ $totalServices }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-400">Empleados activos</span>
                    <span class="text-lg font-bold text-white">{{ $totalEmployees }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-400">Sucursales activas</span>
                    <span class="text-lg font-bold text-white">{{ $totalLocations }}</span>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-6">
            <h3 class="mb-4 text-lg font-bold text-white">Acciones rapidas</h3>
            <div class="space-y-3">
                <a href="{{ route('business.appointments.create') }}" class="flex items-center gap-3 rounded-lg border border-slate-700 bg-slate-800/50 px-4 py-3 text-sm font-medium text-slate-200 transition hover:border-emerald-600 hover:bg-emerald-600/10 hover:text-emerald-200">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Nueva Cita
                </a>
                <a href="{{ route('business.services.create') }}" class="flex items-center gap-3 rounded-lg border border-slate-700 bg-slate-800/50 px-4 py-3 text-sm font-medium text-slate-200 transition hover:border-emerald-600 hover:bg-emerald-600/10 hover:text-emerald-200">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Nuevo Servicio
                </a>
                <a href="{{ route('business.employees.create') }}" class="flex items-center gap-3 rounded-lg border border-slate-700 bg-slate-800/50 px-4 py-3 text-sm font-medium text-slate-200 transition hover:border-emerald-600 hover:bg-emerald-600/10 hover:text-emerald-200">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Nuevo Empleado
                </a>
                <a href="{{ route('business.reports.index') }}" class="flex items-center gap-3 rounded-lg border border-slate-700 bg-slate-800/50 px-4 py-3 text-sm font-medium text-slate-200 transition hover:border-emerald-600 hover:bg-emerald-600/10 hover:text-emerald-200">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Ver Reportes
                </a>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <!-- Top Services -->
        <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-6">
            <h3 class="mb-4 text-lg font-bold text-white">Servicios Mas Solicitados</h3>
            @if(count($topServices) > 0)
                <div class="space-y-3">
                    @foreach($topServices as $service)
                        <div>
                            <div class="mb-1 flex justify-between text-sm">
                                <span class="text-slate-300">{{ $service['nombre'] }}</span>
                                <span class="font-semibold text-white">{{ $service['total'] }} citas</span>
                            </div>
                            <div class="h-2 w-full rounded-full bg-slate-800">
                                <div class="h-2 rounded-full bg-emerald-500" style="width: {{ ($service['total'] / max(array_column($topServices, 'total'))) * 100 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="py-8 text-center text-sm text-slate-500">No hay datos disponibles</p>
            @endif
        </div>

        <!-- Employee Performance -->
        <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-6">
            <h3 class="mb-4 text-lg font-bold text-white">Rendimiento por Empleado</h3>
            @if(count($employeePerformance) > 0)
                <div class="space-y-3">
                    @foreach($employeePerformance as $emp)
                        <div>
                            <div class="mb-1 flex justify-between text-sm">
                                <span class="text-slate-300">{{ $emp['nombre'] }}</span>
                                <span class="font-semibold text-white">{{ $emp['total'] }} citas</span>
                            </div>
                            <div class="h-2 w-full rounded-full bg-slate-800">
                                <div class="h-2 rounded-full bg-blue-500" style="width: {{ ($emp['total'] / max(array_column($employeePerformance, 'total'))) * 100 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="py-8 text-center text-sm text-slate-500">No hay datos disponibles</p>
            @endif
        </div>
    </div>

    <!-- Today's Appointments -->
    <div class="rounded-xl border border-slate-800 bg-slate-950/50">
        <div class="border-b border-slate-800 px-6 py-4">
            <h3 class="text-lg font-bold text-white">Citas de Hoy</h3>
        </div>
        <div class="divide-y divide-slate-800">
            @forelse($todayAppointments as $appointment)
                <div class="px-6 py-4 transition hover:bg-slate-800/30">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-500/15 text-lg font-semibold text-emerald-300">
                                {{ substr($appointment->user?->nombre ?? 'U', 0, 1) }}{{ substr($appointment->user?->apellidos ?? '', 0, 1) }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-white">
                                    {{ $appointment->user?->nombre }} {{ $appointment->user?->apellidos }}
                                </p>
                                <p class="text-sm text-slate-400">{{ $appointment->service?->nombre }}</p>
                                @if($appointment->employee)
                                    <p class="text-xs text-slate-500">con {{ $appointment->employee->nombre }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-white">
                                {{ \Carbon\Carbon::parse($appointment->fecha_hora_inicio)->format('H:i') }}
                            </p>
                            @php
                                $statusLabel = match($appointment->estado) {
                                    'pending' => 'Pendiente',
                                    'confirmed' => 'Confirmada',
                                    'completed' => 'Completada',
                                    'cancelled' => 'Cancelada',
                                    default => $appointment->estado,
                                };
                                $statusClass = match($appointment->estado) {
                                    'pending' => 'bg-amber-500/15 text-amber-200',
                                    'confirmed' => 'bg-blue-500/15 text-blue-200',
                                    'completed' => 'bg-emerald-500/15 text-emerald-200',
                                    'cancelled' => 'bg-rose-500/15 text-rose-200',
                                    default => 'bg-slate-600/20 text-slate-300',
                                };
                            @endphp
                            <span class="mt-1 inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $statusClass }}">
                                {{ $statusLabel }}
                            </span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-6 py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <p class="mt-2 text-sm font-semibold text-slate-300">Sin citas para hoy</p>
                    <p class="mt-1 text-xs text-slate-500">Las citas de hoy apareceran aqui.</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Upcoming Appointments -->
    <div class="rounded-xl border border-slate-800 bg-slate-950/50">
        <div class="flex items-center justify-between border-b border-slate-800 px-6 py-4">
            <h3 class="text-lg font-bold text-white">Proximas Citas</h3>
            <a href="{{ route('business.appointments.index') }}" class="text-xs font-semibold text-emerald-300 transition hover:text-emerald-200">
                Ver todas &rarr;
            </a>
        </div>
        <div class="divide-y divide-slate-800">
            @forelse($upcomingAppointments as $appointment)
                <div class="px-6 py-4 transition hover:bg-slate-800/30">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-500/15 text-lg font-semibold text-blue-300">
                                {{ substr($appointment['user']['nombre'] ?? 'U', 0, 1) }}{{ substr($appointment['user']['apellidos'] ?? '', 0, 1) }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-white">
                                    {{ $appointment['user']['nombre'] }} {{ $appointment['user']['apellidos'] }}
                                </p>
                                <p class="text-sm text-slate-400">{{ $appointment['service']['nombre'] }}</p>
                                @if(!empty($appointment['employee']))
                                    <p class="text-xs text-slate-500">con {{ $appointment['employee']['nombre'] }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-white">
                                {{ \Carbon\Carbon::parse($appointment['fecha_hora_inicio'])->format('d/m/Y') }}
                            </p>
                            <p class="text-sm text-slate-400">
                                {{ \Carbon\Carbon::parse($appointment['fecha_hora_inicio'])->format('H:i') }}
                            </p>
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-6 py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="mt-2 text-sm font-semibold text-slate-300">Sin citas proximas</p>
                    <p class="mt-1 text-xs text-slate-500">Las citas confirmadas apareceran aqui.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
