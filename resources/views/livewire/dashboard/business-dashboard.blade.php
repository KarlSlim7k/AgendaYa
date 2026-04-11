<div class="space-y-6">

    {{-- Period Selector --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-bold text-white">Resumen general</h2>
        </div>
        <div class="flex gap-1.5 rounded-xl border border-slate-800 bg-slate-950/60 p-1.5">
            @foreach(['today' => 'Hoy', 'week' => 'Semana', 'month' => 'Mes', 'year' => 'Año'] as $key => $label)
                <button wire:click="$set('selectedPeriod', '{{ $key }}')"
                        class="rounded-lg px-4 py-1.5 text-sm font-medium transition-all duration-200
                               {{ $selectedPeriod === $key
                                   ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/25'
                                   : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">

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

    {{-- Charts Row --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">

        {{-- Top Services --}}
        <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-6">
            <h3 class="mb-5 text-sm font-semibold uppercase tracking-widest text-slate-400">Servicios Más Solicitados</h3>
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
                <div class="flex flex-col items-center py-10 text-center">
                    <div class="rounded-full bg-slate-800 p-4">
                        <svg class="h-8 w-8 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                        </svg>
                    </div>
                    <p class="mt-3 text-sm text-slate-500">Sin datos disponibles</p>
                </div>
            @endif
        </div>

        {{-- Employee Performance --}}
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
                <div class="flex flex-col items-center py-10 text-center">
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

    {{-- Upcoming Appointments --}}
    <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900/60">
        <div class="border-b border-slate-800 px-6 py-4">
            <h3 class="font-bold text-white">Próximas Citas</h3>
        </div>
        <div class="divide-y divide-slate-800/60">
            @forelse($upcomingAppointments as $appointment)
                <div class="flex items-center justify-between gap-4 px-6 py-4 transition hover:bg-slate-800/30">
                    <div class="flex items-center gap-4">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-blue-500/20 to-blue-600/10 text-sm font-bold text-blue-300 ring-1 ring-blue-500/20">
                            {{ strtoupper(substr($appointment['user']['nombre'], 0, 1) . substr($appointment['user']['apellidos'] ?? '', 0, 1)) }}
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
                        <p class="text-sm font-bold text-white">
                            {{ \Carbon\Carbon::parse($appointment['fecha_hora_inicio'])->format('H:i') }}
                        </p>
                        <p class="text-xs text-slate-400">
                            {{ \Carbon\Carbon::parse($appointment['fecha_hora_inicio'])->format('d/m/Y') }}
                        </p>
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center px-6 py-14 text-center">
                    <div class="rounded-full bg-slate-800/60 p-5">
                        <svg class="h-10 w-10 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="mt-4 text-sm font-semibold text-slate-300">Sin citas próximas</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
