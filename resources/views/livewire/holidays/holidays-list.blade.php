<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-bold text-white">Gestión de Días Festivos</h2>
            <p class="text-sm text-slate-400">Configura días no laborables y excepciones de horario</p>
        </div>
        <button wire:click="create"
                class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nuevo Día Festivo
        </button>
    </div>

    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
             class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
            {{ session('message') }}
        </div>
    @endif

    {{-- Location Filter --}}
    <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-6">
        <label for="selectedLocation" class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Sucursal</label>
        <select wire:model.live="selectedLocation" id="selectedLocation"
                class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
            <option value="" class="bg-slate-900">Seleccionar sucursal</option>
            @foreach($locations as $location)
                <option value="{{ $location->id }}" class="bg-slate-900">{{ $location->nombre }}</option>
            @endforeach
        </select>
    </div>

    {{-- Holidays List --}}
    @if($holidays->count() > 0)
        <div class="space-y-3">
            @foreach($holidays as $holiday)
                <div class="flex items-center justify-between rounded-xl border border-slate-800 bg-slate-900/60 p-4 transition hover:bg-slate-900/80">
                    <div class="flex items-center gap-4 flex-1">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-amber-500/15 text-amber-300">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-base font-bold text-white">{{ $holiday->nombre }}</h3>
                            @if($holiday->descripcion)
                                <p class="text-xs text-slate-400 mt-1">{{ $holiday->descripcion }}</p>
                            @endif
                            <div class="flex items-center gap-3 mt-2">
                                <span class="text-xs text-slate-500">
                                    {{ Carbon\Carbon::parse($holiday->fecha)->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
                                </span>
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $holiday->tipo === 'cerrado' ? 'bg-rose-500/15 text-rose-200 ring-1 ring-rose-500/30' : 'bg-blue-500/15 text-blue-200 ring-1 ring-blue-500/30' }}">
                                    {{ $holiday->tipo === 'cerrado' ? 'Cerrado' : 'Horario Especial' }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button wire:click="edit({{ $holiday->id }})"
                                class="text-xs font-semibold text-blue-400 transition hover:text-blue-300">
                            Editar
                        </button>
                        <button wire:click="delete({{ $holiday->id }})"
                                wire:confirm="¿Estás seguro de eliminar este día festivo?"
                                class="text-xs font-semibold text-rose-400 transition hover:text-rose-300">
                            Eliminar
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="flex flex-col items-center justify-center rounded-xl border border-slate-800 bg-slate-900/60 px-6 py-14 text-center">
            <div class="rounded-full bg-slate-800/60 p-5">
                <svg class="h-10 w-10 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <p class="mt-4 text-sm font-semibold text-slate-300">No hay días festivos configurados</p>
            <p class="mt-1 text-xs text-slate-500">Los días festivos aparecerán aquí.</p>
            @if($selectedLocation)
                <button wire:click="create"
                        class="mt-4 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                    Agregar Día Festivo
                </button>
            @endif
        </div>
    @endif

    {{-- Modal --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm"
             wire:click.self="closeModal">
            <div class="relative w-full max-w-lg rounded-xl border border-slate-800 bg-slate-900 p-6 shadow-2xl mx-4">

                <div class="mb-5 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-white">
                        {{ $editMode ? 'Editar Día Festivo' : 'Nuevo Día Festivo' }}
                    </h3>
                    <button wire:click="closeModal"
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-800 hover:text-white">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form wire:submit="save" class="space-y-4">
                    <div class="space-y-4">
                        @if($locations->count() > 1)
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">
                                    Sucursal <span class="text-rose-400">*</span>
                                </label>
                                <select wire:model="business_location_id"
                                        class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                                    <option value="" class="bg-slate-900">Seleccionar sucursal</option>
                                    @foreach($locations as $location)
                                        <option value="{{ $location->id }}" class="bg-slate-900">{{ $location->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('business_location_id') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                            </div>
                        @else
                            <input type="hidden" wire:model="business_location_id">
                        @endif

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">
                                Nombre <span class="text-rose-400">*</span>
                            </label>
                            <input type="text" wire:model="nombre"
                                   placeholder="Ej: Año Nuevo, Día de la Constitución..."
                                   class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            @error('nombre') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">
                                Fecha <span class="text-rose-400">*</span>
                            </label>
                            <input type="date" wire:model="fecha"
                                   class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 [color-scheme:dark]">
                            @error('fecha') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">
                                Tipo <span class="text-rose-400">*</span>
                            </label>
                            <select wire:model="tipo"
                                    class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                                <option value="cerrado" class="bg-slate-900">Cerrado (no laborable)</option>
                                <option value="horario_especial" class="bg-slate-900">Horario especial</option>
                            </select>
                            @error('tipo') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">
                                Descripción
                            </label>
                            <textarea wire:model="descripcion" rows="3"
                                      placeholder="Notas adicionales sobre este día festivo..."
                                      class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"></textarea>
                            @error('descripcion') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-slate-800 pt-4">
                        <button type="button" wire:click="closeModal"
                                class="rounded-lg border border-slate-700 px-4 py-2 text-sm font-medium text-slate-400 transition hover:bg-slate-800 hover:text-white">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                            {{ $editMode ? 'Actualizar' : 'Crear' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Loading --}}
    <div wire:loading class="fixed top-4 right-4 z-50 rounded-lg bg-emerald-600/90 px-4 py-2 text-sm font-medium text-white shadow-lg backdrop-blur">
        Cargando...
    </div>
</div>
