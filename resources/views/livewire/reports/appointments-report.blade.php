<div class="space-y-6">

    @if(session('error'))
        <div class="rounded-xl border border-rose-500/20 bg-rose-500/10 px-4 py-3 text-sm text-rose-300">
            {{ session('error') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-bold text-white">Reporte de Citas</h2>
            <p class="text-sm text-slate-400">Historial y análisis de citas del negocio</p>
        </div>
        <button wire:click="exportToCsv"
                class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Exportar CSV
        </button>
    </div>

    {{-- Filters --}}
    <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-6">
        <h3 class="mb-4 text-xs font-bold uppercase tracking-widest text-slate-500">Filtros</h3>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-5">

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Fecha Inicio</label>
                <input type="date" wire:model.live="fechaInicio"
                       class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 [color-scheme:dark]">
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Fecha Fin</label>
                <input type="date" wire:model.live="fechaFin"
                       class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 [color-scheme:dark]">
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Servicio</label>
                <select wire:model.live="serviceId"
                        class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    <option value="" class="bg-slate-900">Todos los servicios</option>
                    @foreach($services as $service)
                        <option value="{{ $service->id }}" class="bg-slate-900">{{ $service->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Empleado</label>
                <select wire:model.live="employeeId"
                        class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    <option value="" class="bg-slate-900">Todos los empleados</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" class="bg-slate-900">{{ $employee->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Estado</label>
                <select wire:model.live="estado"
                        class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    <option value="" class="bg-slate-900">Todos los estados</option>
                    <option value="pending" class="bg-slate-900">Pendiente</option>
                    <option value="confirmed" class="bg-slate-900">Confirmada</option>
                    <option value="completed" class="bg-slate-900">Completada</option>
                    <option value="cancelled" class="bg-slate-900">Cancelada</option>
                    <option value="no_show" class="bg-slate-900">No Show</option>
                </select>
            </div>
        </div>

        <div class="mt-4">
            <button wire:click="resetFilters"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-700 px-4 py-2 text-sm font-medium text-slate-400 transition hover:bg-slate-800 hover:text-white">
                Limpiar Filtros
            </button>
        </div>
    </div>

    {{-- Results count --}}
    @if($appointments->total() > 0)
        <div class="flex items-center gap-2 rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3">
            <svg class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-sm text-emerald-300">
                Se encontraron <span class="font-bold">{{ $appointments->total() }}</span> citas en el período seleccionado
            </span>
        </div>
    @endif

    {{-- Table --}}
    <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900/60">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-slate-800">
                        <th class="px-6 py-3 text-left text-[10px] font-bold uppercase tracking-widest text-slate-500">Fecha/Hora</th>
                        <th class="px-6 py-3 text-left text-[10px] font-bold uppercase tracking-widest text-slate-500">Cliente</th>
                        <th class="px-6 py-3 text-left text-[10px] font-bold uppercase tracking-widest text-slate-500">Servicio</th>
                        <th class="px-6 py-3 text-left text-[10px] font-bold uppercase tracking-widest text-slate-500">Empleado</th>
                        <th class="px-6 py-3 text-left text-[10px] font-bold uppercase tracking-widest text-slate-500">Estado</th>
                        <th class="px-6 py-3 text-left text-[10px] font-bold uppercase tracking-widest text-slate-500">Precio</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($appointments as $appointment)
                        <tr class="border-b border-slate-800/60 transition hover:bg-slate-800/30">
                            <td class="px-6 py-4">
                                <p class="text-sm text-white">{{ $appointment->fecha_hora_inicio->format('d/m/Y') }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ $appointment->fecha_hora_inicio->format('H:i') }} — {{ $appointment->fecha_hora_fin->format('H:i') }}
                                </p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-semibold text-white">{{ $appointment->user->nombre }}</p>
                                <p class="text-xs text-slate-500">{{ $appointment->user->email }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm text-slate-300">{{ $appointment->service->nombre }}</p>
                                <p class="text-xs text-slate-500">{{ $appointment->service->duracion_minutos }} min</p>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-300">
                                {{ $appointment->employee->nombre }}
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
                                    $statusLabels = [
                                        'pending'   => 'Pendiente',
                                        'confirmed' => 'Confirmada',
                                        'completed' => 'Completada',
                                        'cancelled' => 'Cancelada',
                                        'no_show'   => 'No Show',
                                    ];
                                @endphp
                                <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold {{ $statusClasses[$appointment->estado] ?? '' }}">
                                    {{ $statusLabels[$appointment->estado] ?? $appointment->estado }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm font-semibold text-emerald-400">
                                ${{ number_format($appointment->service->precio, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-14 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="rounded-full bg-slate-800/60 p-5">
                                        <svg class="h-10 w-10 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                    <p class="mt-4 text-sm font-semibold text-slate-300">No se encontraron citas</p>
                                    <p class="mt-1 text-xs text-slate-500">Prueba con diferentes filtros</p>
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
</div>
