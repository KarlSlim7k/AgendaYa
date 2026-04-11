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

    {{-- Tabs --}}
    <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900/60">
        <div class="border-b border-slate-800">
            <div class="flex">
                <button class="border-b-2 border-emerald-500 px-6 py-3 text-sm font-semibold text-emerald-300">
                    Horarios Base
                </button>
                <a href="{{ route('business.schedules.exceptions') }}"
                   class="border-b-2 border-transparent px-6 py-3 text-sm font-medium text-slate-400 transition hover:border-slate-600 hover:text-slate-200">
                    Excepciones
                </a>
            </div>
        </div>

        <div class="p-6">
            @if($locations->count() > 0)

                {{-- Location Selector --}}
                <div class="mb-6">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Sucursal</label>
                    <select wire:model.live="selectedLocationId"
                            class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 md:w-1/2">
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}" class="bg-slate-900">{{ $location->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Schedule Grid --}}
                @if($selectedLocationId)
                    <div class="space-y-3">
                        @foreach($dias as $dia => $nombreDia)
                            <div wire:key="day-{{ $dia }}"
                                 class="rounded-xl border border-slate-800 bg-slate-800/20 p-4">
                                <div class="flex flex-wrap items-center gap-4">
                                    <div class="w-28 shrink-0">
                                        <span class="text-sm font-semibold {{ $templates[$dia]['activo'] ? 'text-white' : 'text-slate-500' }}">
                                            {{ $nombreDia }}
                                        </span>
                                    </div>

                                    @if($templates[$dia]['activo'])
                                        <div class="flex flex-1 flex-wrap items-center gap-3">
                                            <div>
                                                <label class="block text-[10px] font-bold uppercase tracking-wide text-slate-500 mb-1">Apertura</label>
                                                <input type="time" wire:model="templates.{{ $dia }}.hora_apertura"
                                                       class="rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 [color-scheme:dark]">
                                            </div>
                                            <span class="mt-5 text-slate-600">—</span>
                                            <div>
                                                <label class="block text-[10px] font-bold uppercase tracking-wide text-slate-500 mb-1">Cierre</label>
                                                <input type="time" wire:model="templates.{{ $dia }}.hora_cierre"
                                                       class="rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 [color-scheme:dark]">
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-sm text-slate-600">Cerrado</span>
                                    @endif

                                    <button wire:click="toggleActivo({{ $dia }})" type="button"
                                            class="ml-auto rounded-full px-2.5 py-0.5 text-[10px] font-bold transition
                                                   {{ $templates[$dia]['activo']
                                                       ? 'bg-emerald-500/15 text-emerald-300 ring-1 ring-emerald-500/30 hover:bg-emerald-500/25'
                                                       : 'bg-slate-700/40 text-slate-500 ring-1 ring-slate-600/30 hover:bg-slate-700/60' }}">
                                        {{ $templates[$dia]['activo'] ? 'Activo' : 'Inactivo' }}
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button wire:click="saveAll" type="button"
                                wire:loading.attr="disabled"
                                class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:opacity-60">
                            <span wire:loading.remove>Guardar Todos los Horarios</span>
                            <span wire:loading>Guardando...</span>
                        </button>
                    </div>
                @endif

            @else
                <div class="flex flex-col items-center py-12 text-center">
                    <div class="rounded-full bg-slate-800/60 p-5">
                        <svg class="h-10 w-10 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <p class="mt-4 text-sm font-semibold text-slate-300">No hay sucursales configuradas</p>
                    <a href="{{ route('business.dashboard') }}"
                       class="mt-3 text-xs font-semibold text-emerald-400 hover:text-emerald-300">
                        Ir al Dashboard
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- Loading --}}
    <div wire:loading class="fixed top-4 right-4 z-50 rounded-lg bg-emerald-600/90 px-4 py-2 text-sm font-medium text-white shadow-lg backdrop-blur">
        Guardando...
    </div>
</div>
