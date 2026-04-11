<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-bold text-white">Gestión de Citas</h2>
            <p class="text-sm text-slate-400">Lista completa de citas del negocio</p>
        </div>
        <a href="{{ route('business.appointments.create') }}"
           class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nueva Cita
        </a>
    </div>

    {{-- Filters --}}
    <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-6">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">

            <div>
                <label for="search" class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Buscar cliente</label>
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       id="search"
                       placeholder="Nombre o email..."
                       class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
            </div>

            <div>
                <label for="estadoFilter" class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Estado</label>
                <select wire:model.live="estadoFilter"
                        id="estadoFilter"
                        class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    <option value="" class="bg-slate-900">Todos los estados</option>
                    @foreach($estados as $key => $label)
                        <option value="{{ $key }}" class="bg-slate-900">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="servicioFilter" class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Servicio</label>
                <select wire:model.live="servicioFilter"
                        id="servicioFilter"
                        class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    <option value="" class="bg-slate-900">Todos los servicios</option>
                    @foreach($servicios as $servicio)
                        <option value="{{ $servicio->id }}" class="bg-slate-900">{{ $servicio->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="empleadoFilter" class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Empleado</label>
                <select wire:model.live="empleadoFilter"
                        id="empleadoFilter"
                        class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    <option value="" class="bg-slate-900">Todos los empleados</option>
                    @foreach($empleados as $empleado)
                        <option value="{{ $empleado->id }}" class="bg-slate-900">{{ $empleado->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="fechaDesde" class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Desde</label>
                <input type="date"
                       wire:model.live="fechaDesde"
                       id="fechaDesde"
                       class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 [color-scheme:dark]">
            </div>

            <div>
                <label for="fechaHasta" class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Hasta</label>
                <input type="date"
                       wire:model.live="fechaHasta"
                       id="fechaHasta"
                       class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 [color-scheme:dark]">
            </div>

            <div class="flex items-end md:col-span-2">
                <button wire:click="resetFilters"
                        type="button"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-700 px-4 py-2.5 text-sm font-medium text-slate-400 transition hover:bg-slate-800 hover:text-white">
                    Limpiar Filtros
                </button>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900/60">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-slate-800">
                        <th class="px-6 py-3 text-left text-[10px] font-bold uppercase tracking-widest text-slate-500">Cliente</th>
                        <th class="px-6 py-3 text-left text-[10px] font-bold uppercase tracking-widest text-slate-500">Servicio</th>
                        <th class="px-6 py-3 text-left text-[10px] font-bold uppercase tracking-widest text-slate-500">Empleado</th>
                        <th class="px-6 py-3 text-left text-[10px] font-bold uppercase tracking-widest text-slate-500">Fecha y Hora</th>
                        <th class="px-6 py-3 text-left text-[10px] font-bold uppercase tracking-widest text-slate-500">Estado</th>
                        <th class="px-6 py-3 text-right text-[10px] font-bold uppercase tracking-widest text-slate-500">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($appointments as $appointment)
                        <tr class="border-b border-slate-800/60 transition hover:bg-slate-800/30">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-500/20 text-sm font-bold text-emerald-300 ring-1 ring-emerald-500/20">
                                        {{ strtoupper(substr(($appointment->user->nombre ?? 'U') . ($appointment->user->apellidos ?? ''), 0, 2)) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-white">
                                            {{ trim(($appointment->user->nombre ?? '') . ' ' . ($appointment->user->apellidos ?? '')) }}
                                        </p>
                                        <p class="text-xs text-slate-500">{{ $appointment->user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm text-slate-300">{{ $appointment->service->nombre }}</p>
                                <p class="text-xs text-slate-500">{{ $appointment->service->duracion_minutos }} min</p>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-300">
                                {{ $appointment->employee->nombre }}
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm text-white">{{ $appointment->fecha_hora_inicio->format('d/m/Y') }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ $appointment->fecha_hora_inicio->format('H:i') }} — {{ $appointment->fecha_hora_fin->format('H:i') }}
                                </p>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $statusClasses = [
                                        'pending'   => 'bg-amber-500/15 text-amber-200 ring-1 ring-amber-500/30',
                                        'confirmed' => 'bg-blue-500/15 text-blue-200 ring-1 ring-blue-500/30',
                                        'completed' => 'bg-emerald-500/15 text-emerald-200 ring-1 ring-emerald-500/30',
                                        'cancelled' => 'bg-rose-500/15 text-rose-200 ring-1 ring-rose-500/30',
                                        'no_show'   => 'bg-slate-500/15 text-slate-300 ring-1 ring-slate-500/30',
                                    ];
                                @endphp
                                <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold {{ $statusClasses[$appointment->estado] ?? 'bg-slate-700/40 text-slate-400' }}">
                                    {{ $estados[$appointment->estado] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button wire:click="viewDetail({{ $appointment->id }})"
                                        class="text-xs font-semibold text-emerald-400 transition hover:text-emerald-300 mr-3">
                                    Ver
                                </button>
                                @if(in_array($appointment->estado, ['pending', 'confirmed']))
                                    <button wire:click="cancelAppointment({{ $appointment->id }})"
                                            wire:confirm="¿Está seguro de cancelar esta cita?"
                                            class="text-xs font-semibold text-rose-400 transition hover:text-rose-300">
                                        Cancelar
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-14 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="rounded-full bg-slate-800/60 p-5">
                                        <svg class="h-10 w-10 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <p class="mt-4 text-sm font-semibold text-slate-300">No hay citas</p>
                                    <p class="mt-1 text-xs text-slate-500">No se encontraron citas con los filtros seleccionados.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($appointments->hasPages())
            <div class="border-t border-slate-800 px-6 py-3">
                {{ $appointments->links() }}
            </div>
        @endif
    </div>

    {{-- Detail Modal --}}
    @if($showDetailModal && $selectedAppointment)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm"
             wire:click.self="closeDetailModal">
            <div class="relative w-full max-w-lg rounded-xl border border-slate-800 bg-slate-900 p-6 shadow-2xl mx-4">

                <div class="mb-5 flex items-center justify-between">
                    <h3 class="font-bold text-white">Detalle de Cita #{{ $selectedAppointment->id }}</h3>
                    <button wire:click="closeDetailModal"
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-800 hover:text-white">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <dl class="space-y-3">
                    @foreach([
                        ['Cliente',   trim(($selectedAppointment->user->nombre ?? '') . ' ' . ($selectedAppointment->user->apellidos ?? ''))],
                        ['Email',     $selectedAppointment->user->email],
                        ['Servicio',  $selectedAppointment->service->nombre],
                        ['Empleado',  $selectedAppointment->employee->nombre],
                        ['Fecha',     $selectedAppointment->fecha_hora_inicio->format('d/m/Y H:i')],
                        ['Duración',  $selectedAppointment->service->duracion_minutos . ' minutos'],
                        ['Estado',    $estados[$selectedAppointment->estado]],
                    ] as [$label, $value])
                        <div class="flex items-center justify-between rounded-lg bg-slate-800/40 px-4 py-2.5">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</dt>
                            <dd class="text-sm font-medium text-white">{{ $value }}</dd>
                        </div>
                    @endforeach

                    @if($selectedAppointment->notas_cliente)
                        <div class="rounded-lg bg-slate-800/40 px-4 py-3">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Notas del cliente</dt>
                            <dd class="text-sm text-slate-300">{{ $selectedAppointment->notas_cliente }}</dd>
                        </div>
                    @endif
                    @if($selectedAppointment->notas_internas)
                        <div class="rounded-lg bg-amber-500/10 border border-amber-500/20 px-4 py-3">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-amber-400 mb-1">Notas internas</dt>
                            <dd class="text-sm text-amber-200">{{ $selectedAppointment->notas_internas }}</dd>
                        </div>
                    @endif
                </dl>

                <div class="mt-5 flex justify-end">
                    <button wire:click="closeDetailModal"
                            class="inline-flex items-center gap-2 rounded-lg border border-slate-700 px-4 py-2 text-sm font-medium text-slate-400 transition hover:bg-slate-800 hover:text-white">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Loading --}}
    <div wire:loading class="fixed top-4 right-4 z-50 rounded-lg bg-emerald-600/90 px-4 py-2 text-sm font-medium text-white shadow-lg backdrop-blur">
        Cargando...
    </div>
</div>
