<div class="space-y-6">

    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
            {{ session('message') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="rounded-xl border border-rose-500/20 bg-rose-500/10 px-4 py-3 text-sm text-rose-300">
            {{ session('error') }}
        </div>
    @endif

    {{-- Tabs + Content --}}
    <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900/60">
        <div class="border-b border-slate-800">
            <div class="flex">
                <a href="{{ route('business.schedules.index') }}"
                   class="border-b-2 border-transparent px-6 py-3 text-sm font-medium text-slate-400 transition hover:border-slate-600 hover:text-slate-200">
                    Horarios Base
                </a>
                <button class="border-b-2 border-emerald-500 px-6 py-3 text-sm font-semibold text-emerald-300">
                    Excepciones
                </button>
            </div>
        </div>

        <div class="p-6">
            @if($locations->count() > 0)

                {{-- Location + Add Button --}}
                <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
                    <div class="flex-1 min-w-[200px] max-w-sm">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Sucursal</label>
                        <select wire:model.live="selectedLocationId"
                                class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}" class="bg-slate-900">{{ $location->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if(!$showForm)
                        <button wire:click="showCreateForm"
                                class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Nueva Excepción
                        </button>
                    @endif
                </div>

                {{-- Form --}}
                @if($showForm)
                    <div class="mb-6 rounded-xl border border-slate-700/60 bg-slate-800/20 p-6">
                        <h3 class="mb-5 text-sm font-semibold uppercase tracking-widest text-slate-400">
                            {{ $exceptionId ? 'Editar Excepción' : 'Nueva Excepción' }}
                        </h3>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Tipo</label>
                                <select wire:model="tipo"
                                        class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                                    <option value="feriado" class="bg-slate-900">Feriado</option>
                                    <option value="vacaciones" class="bg-slate-900">Vacaciones</option>
                                    <option value="cierre" class="bg-slate-900">Cierre Temporal</option>
                                </select>
                                @error('tipo') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Motivo</label>
                                <input type="text" wire:model="motivo" placeholder="Ej: Día de Año Nuevo"
                                       class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                                @error('motivo') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Fecha</label>
                                <input type="date" wire:model="fecha"
                                       class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 [color-scheme:dark]">
                                @error('fecha') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                            </div>

                            <div class="flex items-center">
                                <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-700/60 bg-slate-800/20 p-3 w-full">
                                    <input type="checkbox" wire:model.live="todo_el_dia"
                                           class="h-4 w-4 rounded border-slate-600 bg-slate-900 text-emerald-600 focus:ring-emerald-500 focus:ring-offset-slate-900">
                                    <div>
                                        <span class="text-sm font-medium text-slate-200">Todo el día</span>
                                        <p class="text-xs text-slate-500">Desactiva para bloquear sólo un rango</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        @if(!$todo_el_dia)
                            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Hora Inicio</label>
                                    <input type="time" wire:model="hora_inicio"
                                           class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 [color-scheme:dark]">
                                    @error('hora_inicio') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Hora Fin</label>
                                    <input type="time" wire:model="hora_fin"
                                           class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 [color-scheme:dark]">
                                    @error('hora_fin') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        @endif

                        <div class="mt-5 flex justify-end gap-3">
                            <button wire:click="cancelForm" type="button"
                                    class="inline-flex items-center gap-2 rounded-lg border border-slate-700 px-4 py-2 text-sm font-medium text-slate-400 transition hover:bg-slate-800 hover:text-white">
                                Cancelar
                            </button>
                            <button wire:click="save" type="button"
                                    class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                {{ $exceptionId ? 'Actualizar' : 'Crear' }}
                            </button>
                        </div>
                    </div>
                @endif

                {{-- Exceptions Table --}}
                @if($selectedLocationId)
                    <div class="overflow-hidden rounded-xl border border-slate-800">
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="border-b border-slate-800 bg-slate-950/40">
                                        <th class="px-6 py-3 text-left text-[10px] font-bold uppercase tracking-widest text-slate-500">Tipo</th>
                                        <th class="px-6 py-3 text-left text-[10px] font-bold uppercase tracking-widest text-slate-500">Motivo</th>
                                        <th class="px-6 py-3 text-left text-[10px] font-bold uppercase tracking-widest text-slate-500">Fecha</th>
                                        <th class="px-6 py-3 text-left text-[10px] font-bold uppercase tracking-widest text-slate-500">Horario</th>
                                        <th class="px-6 py-3 text-right text-[10px] font-bold uppercase tracking-widest text-slate-500">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($exceptions as $exception)
                                        <tr wire:key="exception-{{ $exception->id }}" class="border-b border-slate-800/60 transition hover:bg-slate-800/30">
                                            <td class="px-6 py-4">
                                                <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold
                                                    {{ $exception->tipo === 'feriado'    ? 'bg-violet-500/15 text-violet-300 ring-1 ring-violet-500/30' : '' }}
                                                    {{ $exception->tipo === 'vacaciones' ? 'bg-blue-500/15 text-blue-300 ring-1 ring-blue-500/30'     : '' }}
                                                    {{ $exception->tipo === 'cierre'     ? 'bg-rose-500/15 text-rose-300 ring-1 ring-rose-500/30'      : '' }}">
                                                    {{ ucfirst($exception->tipo) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-slate-300">{{ $exception->motivo }}</td>
                                            <td class="px-6 py-4 text-sm text-slate-300">{{ $exception->fecha->format('d/m/Y') }}</td>
                                            <td class="px-6 py-4 text-sm text-slate-400">
                                                {{ $exception->todo_el_dia ? 'Todo el día' : ($exception->hora_inicio . ' — ' . $exception->hora_fin) }}
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <button wire:click="edit({{ $exception->id }})"
                                                        class="text-xs font-semibold text-blue-400 transition hover:text-blue-300 mr-3">
                                                    Editar
                                                </button>
                                                <button wire:click="delete({{ $exception->id }})"
                                                        wire:confirm="¿Eliminar esta excepción?"
                                                        class="text-xs font-semibold text-rose-400 transition hover:text-rose-300">
                                                    Eliminar
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-6 py-10 text-center text-sm text-slate-500">
                                                No hay excepciones configuradas.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="border-t border-slate-800 px-6 py-3">
                            {{ $exceptions->links() }}
                        </div>
                    </div>
                @endif

            @else
                <div class="flex flex-col items-center py-12 text-center">
                    <p class="text-sm font-semibold text-slate-300 mb-3">No hay sucursales configuradas</p>
                    <a href="{{ route('business.dashboard') }}"
                       class="text-xs font-semibold text-emerald-400 hover:text-emerald-300">
                        Ir al Dashboard
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- Loading --}}
    <div wire:loading class="fixed top-4 right-4 z-50 rounded-lg bg-emerald-600/90 px-4 py-2 text-sm font-medium text-white shadow-lg backdrop-blur">
        Cargando...
    </div>

    {{-- Delete Confirm Modal --}}
    @if($confirmingDeletion)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-xl border border-slate-800 bg-slate-900 p-6 shadow-2xl mx-4">
                <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-rose-500/15 ring-1 ring-rose-500/30">
                    <svg class="h-6 w-6 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-white mb-1">Confirmar eliminación</h3>
                <p class="text-sm text-slate-400 mb-5">¿Estás seguro de que deseas eliminar esta excepción? Esta acción no se puede deshacer.</p>
                <div class="flex justify-end gap-3">
                    <button wire:click="cancelDelete"
                            class="inline-flex items-center gap-2 rounded-lg border border-slate-700 px-4 py-2 text-sm font-medium text-slate-400 transition hover:bg-slate-800 hover:text-white">
                        Cancelar
                    </button>
                    <button wire:click="delete"
                            class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-700">
                        Eliminar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
