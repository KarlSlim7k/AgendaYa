<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-bold text-white">Gestión de Sucursales</h2>
            <p class="text-sm text-slate-400">Administra las sucursales de tu negocio</p>
        </div>
        <button wire:click="create"
                class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nueva Sucursal
        </button>
    </div>

    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
             class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
            {{ session('message') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="rounded-xl border border-rose-500/20 bg-rose-500/10 px-4 py-3 text-sm text-rose-300">
            {{ session('error') }}
        </div>
    @endif

    {{-- Locations Grid --}}
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
        @forelse($locations as $location)
            <div class="group rounded-xl border border-slate-800 bg-slate-900/60 p-6 transition hover:border-emerald-500/40 hover:bg-slate-900/80">
                <div class="mb-4 flex items-start justify-between">
                    <div class="flex-1">
                        <div class="mb-2 flex items-center gap-2">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-500/15 text-emerald-300">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                            <h3 class="text-base font-bold text-white">{{ $location->nombre }}</h3>
                        </div>
                        @if($location->descripcion)
                            <p class="text-xs text-slate-400 line-clamp-2">{{ $location->descripcion }}</p>
                        @endif
                    </div>
                    <button wire:click="toggleActivo({{ $location->id }})"
                            class="rounded-full px-2 py-0.5 text-[10px] font-bold transition
                                   {{ $location->activo
                                       ? 'bg-emerald-500/15 text-emerald-300 ring-1 ring-emerald-500/30 hover:bg-emerald-500/25'
                                       : 'bg-slate-700/40 text-slate-400 ring-1 ring-slate-600/30 hover:bg-slate-700/60' }}">
                        {{ $location->activo ? 'Activa' : 'Inactiva' }}
                    </button>
                </div>

                <div class="space-y-2 text-sm text-slate-300">
                    <div class="flex items-start gap-2">
                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>{{ $location->direccion }}, {{ $location->ciudad }}</span>
                    </div>
                    @if($location->telefono)
                        <div class="flex items-center gap-2">
                            <svg class="h-4 w-4 shrink-0 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            <span>{{ $location->telefono }}</span>
                        </div>
                    @endif
                </div>

                <div class="mt-4 flex items-center justify-end gap-2 border-t border-slate-800 pt-4">
                    <button wire:click="edit({{ $location->id }})"
                            class="text-xs font-semibold text-blue-400 transition hover:text-blue-300">
                        Editar
                    </button>
                    <button wire:click="delete({{ $location->id }})"
                            wire:confirm="¿Estás seguro de eliminar esta sucursal?"
                            class="text-xs font-semibold text-rose-400 transition hover:text-rose-300">
                        Eliminar
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-full flex flex-col items-center justify-center rounded-xl border border-slate-800 bg-slate-900/60 px-6 py-14 text-center">
                <div class="rounded-full bg-slate-800/60 p-5">
                    <svg class="h-10 w-10 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <p class="mt-4 text-sm font-semibold text-slate-300">No hay sucursales registradas</p>
                <p class="mt-1 text-xs text-slate-500">Crea tu primera sucursal para comenzar.</p>
                <button wire:click="create"
                        class="mt-4 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                    Nueva Sucursal
                </button>
            </div>
        @endforelse
    </div>

    {{-- Modal --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm"
             wire:click.self="closeModal">
            <div class="relative w-full max-w-2xl rounded-xl border border-slate-800 bg-slate-900 p-6 shadow-2xl mx-4 max-h-[90vh] overflow-y-auto">

                <div class="mb-5 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-white">
                        {{ $editMode ? 'Editar Sucursal' : 'Nueva Sucursal' }}
                    </h3>
                    <button wire:click="closeModal"
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-800 hover:text-white">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form wire:submit="save" class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">
                                Nombre de la sucursal <span class="text-rose-400">*</span>
                            </label>
                            <input type="text" wire:model="nombre"
                                   class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            @error('nombre') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">
                                Dirección <span class="text-rose-400">*</span>
                            </label>
                            <input type="text" wire:model="direccion"
                                   class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            @error('direccion') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">
                                Ciudad <span class="text-rose-400">*</span>
                            </label>
                            <input type="text" wire:model="ciudad"
                                   class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            @error('ciudad') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">
                                Estado <span class="text-rose-400">*</span>
                            </label>
                            <input type="text" wire:model="estado"
                                   class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            @error('estado') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">
                                Código Postal <span class="text-rose-400">*</span>
                            </label>
                            <input type="text" wire:model="codigo_postal"
                                   class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            @error('codigo_postal') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">
                                Teléfono
                            </label>
                            <input type="text" wire:model="telefono"
                                   class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            @error('telefono') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">
                                Email
                            </label>
                            <input type="email" wire:model="email"
                                   class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            @error('email') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">
                                Descripción
                            </label>
                            <textarea wire:model="descripcion" rows="3"
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
                            {{ $editMode ? 'Actualizar' : 'Crear' }} Sucursal
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
