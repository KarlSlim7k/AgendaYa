<div class="space-y-6">

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

    @if (!$business)
        <div class="rounded-xl border border-slate-800 bg-slate-900/60 px-6 py-10 text-center text-sm text-slate-500">
            Cargando información del negocio...
        </div>
    @else

    <form wire:submit="save" class="space-y-6">

        {{-- Main Info Card --}}
        <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-6">
            <h3 class="mb-5 text-sm font-semibold uppercase tracking-widest text-slate-400">Información General</h3>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                {{-- Nombre --}}
                <div class="md:col-span-2">
                    <label for="nombre" class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">
                        Nombre del Negocio <span class="text-rose-400">*</span>
                    </label>
                    <input type="text" id="nombre" wire:model="nombre"
                           class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    @error('nombre')
                        <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Razón Social --}}
                <div>
                    <label for="razon_social" class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">
                        Razón Social
                    </label>
                    <input type="text" id="razon_social" wire:model="razon_social"
                           class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    @error('razon_social')
                        <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- RFC --}}
                <div>
                    <label for="rfc" class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">
                        RFC
                    </label>
                    <input type="text" id="rfc" wire:model="rfc" placeholder="ABC123456XYZ"
                           class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm uppercase text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    @error('rfc')
                        <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">
                        Email <span class="text-rose-400">*</span>
                    </label>
                    <input type="email" id="email" wire:model="email"
                           class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    @error('email')
                        <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Teléfono --}}
                <div>
                    <label for="telefono" class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">
                        Teléfono <span class="text-rose-400">*</span>
                    </label>
                    <input type="text" id="telefono" wire:model="telefono" placeholder="+52 55 1234 5678"
                           class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    @error('telefono')
                        <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Categoría --}}
                <div class="md:col-span-2">
                    <label for="categoria" class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">
                        Categoría <span class="text-rose-400">*</span>
                    </label>
                    <select id="categoria" wire:model="categoria"
                            class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        <option value="" class="bg-slate-900">Selecciona una categoría</option>
                        <option value="peluqueria" class="bg-slate-900">Peluquería</option>
                        <option value="clinica" class="bg-slate-900">Clínica</option>
                        <option value="taller" class="bg-slate-900">Taller</option>
                        <option value="spa" class="bg-slate-900">Spa</option>
                        <option value="consultorio" class="bg-slate-900">Consultorio</option>
                        <option value="gimnasio" class="bg-slate-900">Gimnasio</option>
                        <option value="restaurante" class="bg-slate-900">Restaurante</option>
                        <option value="otro" class="bg-slate-900">Otro</option>
                    </select>
                    @error('categoria')
                        <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Descripción --}}
                <div class="md:col-span-2">
                    <label for="descripcion" class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">
                        Descripción
                    </label>
                    <textarea id="descripcion" wire:model="descripcion" rows="4"
                              placeholder="Describe tu negocio..."
                              class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"></textarea>
                    @error('descripcion')
                        <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-slate-600">Máximo 1000 caracteres</p>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center justify-end gap-3">
            <button type="button"
                    onclick="window.location.href='{{ route('business.dashboard') }}'"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-700 px-4 py-2 text-sm font-medium text-slate-400 transition hover:bg-slate-800 hover:text-white">
                Cancelar
            </button>
            <button type="submit"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:opacity-60">
                <span wire:loading.remove>Guardar Cambios</span>
                <span wire:loading>Guardando...</span>
            </button>
        </div>
    </form>

    {{-- Sucursales --}}
    <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900/60">
        <div class="border-b border-slate-800 px-6 py-4">
            <h3 class="font-bold text-white">Sucursales</h3>
            <p class="text-xs text-slate-500">Ubicaciones registradas para este negocio</p>
        </div>
        <div class="divide-y divide-slate-800/60">
            @forelse ($business->locations as $location)
                <div class="flex items-center justify-between px-6 py-4">
                    <div>
                        <p class="text-sm font-semibold text-white">{{ $location->nombre }}</p>
                        <p class="text-xs text-slate-400">{{ $location->direccion }}, {{ $location->ciudad }}</p>
                    </div>
                    <span class="rounded-full bg-emerald-500/15 px-2.5 py-0.5 text-[10px] font-bold text-emerald-300 ring-1 ring-emerald-500/30">
                        Activa
                    </span>
                </div>
            @empty
                <div class="px-6 py-8 text-center text-sm text-slate-500">
                    No hay sucursales registradas.
                </div>
            @endforelse
        </div>
    </div>

    @endif
</div>
