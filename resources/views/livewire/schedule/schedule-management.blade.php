<div class="space-y-6">

    @if(session()->has('message'))
        <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
            {{ session('message') }}
        </div>
    @endif

    {{-- Location Selector --}}
    @if($locations->count() > 1)
        <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4">
            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Sucursal</label>
            <select wire:model.live="selectedLocationId"
                    class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 md:w-64">
                @foreach($locations as $location)
                    <option value="{{ $location->id }}" class="bg-slate-900">{{ $location->nombre }}</option>
                @endforeach
            </select>
        </div>
    @endif

    {{-- Weekly Schedule --}}
    <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900/60">
        <div class="border-b border-slate-800 px-6 py-4">
            <h3 class="font-bold text-white">Horario Semanal</h3>
            <p class="text-xs text-slate-500">Configura los días y horarios de atención</p>
        </div>
        <div class="divide-y divide-slate-800/60 px-6 py-2">
            @foreach($schedules as $schedule)
                <div class="flex flex-wrap items-center gap-4 py-4">
                    <div class="flex w-36 items-center gap-3">
                        <input type="checkbox"
                               wire:click="toggleDay({{ $schedule['dia_semana'] }})"
                               {{ $schedule['activo'] ? 'checked' : '' }}
                               class="h-4 w-4 rounded border-slate-600 bg-slate-900 text-emerald-600 focus:ring-emerald-500 focus:ring-offset-slate-900">
                        <span class="text-sm font-medium {{ $schedule['activo'] ? 'text-white' : 'text-slate-500' }}">
                            {{ $schedule['nombre'] }}
                        </span>
                    </div>

                    @if($schedule['activo'])
                        <div class="flex flex-1 flex-wrap items-center gap-3">
                            <input type="time"
                                   wire:model="schedules.{{ $loop->index }}.hora_apertura"
                                   class="rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 [color-scheme:dark]">
                            <span class="text-slate-600">—</span>
                            <input type="time"
                                   wire:model="schedules.{{ $loop->index }}.hora_cierre"
                                   class="rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 [color-scheme:dark]">
                            <button wire:click="saveSchedule({{ $schedule['dia_semana'] }})"
                                    class="rounded-lg bg-emerald-600/20 px-3 py-1.5 text-xs font-semibold text-emerald-300 transition hover:bg-emerald-600/30">
                                Guardar
                            </button>
                        </div>
                    @else
                        <span class="text-sm text-slate-600">Cerrado</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Exceptions --}}
    <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900/60">
        <div class="flex items-center justify-between border-b border-slate-800 px-6 py-4">
            <div>
                <h3 class="font-bold text-white">Excepciones de Horario</h3>
                <p class="text-xs text-slate-500">Feriados, vacaciones y cierres temporales</p>
            </div>
            <button wire:click="openExceptionModal"
                    class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nueva Excepción
            </button>
        </div>
        <div class="divide-y divide-slate-800/60">
            @forelse($exceptions as $exception)
                <div class="flex items-center justify-between gap-4 px-6 py-4 transition hover:bg-slate-800/30">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold
                                {{ $exception['tipo'] === 'feriado'    ? 'bg-violet-500/15 text-violet-300 ring-1 ring-violet-500/30' : '' }}
                                {{ $exception['tipo'] === 'vacaciones' ? 'bg-blue-500/15 text-blue-300 ring-1 ring-blue-500/30'     : '' }}
                                {{ $exception['tipo'] === 'cierre'     ? 'bg-rose-500/15 text-rose-300 ring-1 ring-rose-500/30'     : '' }}">
                                {{ ucfirst($exception['tipo']) }}
                            </span>
                            <span class="text-sm font-semibold text-white">{{ $exception['motivo'] }}</span>
                        </div>
                        <p class="text-xs text-slate-500">
                            {{ \Carbon\Carbon::parse($exception['fecha_inicio'])->format('d/m/Y') }}
                            @if($exception['fecha_inicio'] !== $exception['fecha_fin'])
                                — {{ \Carbon\Carbon::parse($exception['fecha_fin'])->format('d/m/Y') }}
                            @endif
                            @if(!$exception['todo_el_dia'])
                                ({{ $exception['hora_inicio'] }} — {{ $exception['hora_fin'] }})
                            @endif
                        </p>
                    </div>
                    <button wire:click="deleteException({{ $exception['id'] }})"
                            wire:confirm="¿Eliminar esta excepción?"
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-500 transition hover:bg-rose-500/10 hover:text-rose-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            @empty
                <div class="px-6 py-10 text-center text-sm text-slate-500">
                    No hay excepciones registradas
                </div>
            @endforelse
        </div>
    </div>

    {{-- Exception Modal --}}
    @if($showExceptionModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-xl border border-slate-800 bg-slate-900 shadow-2xl mx-4">
                <div class="border-b border-slate-800 px-6 py-4">
                    <h3 class="font-bold text-white">Nueva Excepción</h3>
                </div>
                <div class="space-y-4 p-6">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Tipo</label>
                        <select wire:model.live="exceptionTipo"
                                class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            <option value="feriado" class="bg-slate-900">Feriado</option>
                            <option value="vacaciones" class="bg-slate-900">Vacaciones</option>
                            <option value="cierre" class="bg-slate-900">Cierre Temporal</option>
                        </select>
                    </div>

                    @if($exceptionTipo === 'feriado')
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Fecha</label>
                            <input type="date" wire:model="exceptionFecha"
                                   class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 [color-scheme:dark]">
                            @error('exceptionFecha') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                        </div>
                    @else
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Fecha Inicio</label>
                                <input type="date" wire:model="exceptionFechaInicio"
                                       class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 [color-scheme:dark]">
                                @error('exceptionFechaInicio') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Fecha Fin</label>
                                <input type="date" wire:model="exceptionFechaFin"
                                       class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 [color-scheme:dark]">
                                @error('exceptionFechaFin') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    @endif

                    <label class="flex cursor-pointer items-center gap-3">
                        <input type="checkbox" wire:model.live="exceptionTodoElDia"
                               class="h-4 w-4 rounded border-slate-600 bg-slate-900 text-emerald-600 focus:ring-emerald-500 focus:ring-offset-slate-900">
                        <span class="text-sm font-medium text-slate-200">Todo el día</span>
                    </label>

                    @if(!$exceptionTodoElDia)
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Hora Inicio</label>
                                <input type="time" wire:model="exceptionHoraInicio"
                                       class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 [color-scheme:dark]">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Hora Fin</label>
                                <input type="time" wire:model="exceptionHoraFin"
                                       class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 [color-scheme:dark]">
                            </div>
                        </div>
                    @endif

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Motivo</label>
                        <input type="text" wire:model="exceptionMotivo"
                               placeholder="Ej: Día de la Independencia"
                               class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        @error('exceptionMotivo') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex justify-end gap-3 border-t border-slate-800 px-6 py-4">
                    <button wire:click="$set('showExceptionModal', false)"
                            class="inline-flex items-center gap-2 rounded-lg border border-slate-700 px-4 py-2 text-sm font-medium text-slate-400 transition hover:bg-slate-800 hover:text-white">
                        Cancelar
                    </button>
                    <button wire:click="saveException"
                            class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                        Guardar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
