<div class="space-y-6">

    <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-6">
        <h2 class="mb-6 text-sm font-semibold uppercase tracking-widest text-slate-400">
            {{ $employeeId ? 'Editar Empleado' : 'Nuevo Empleado' }}
        </h2>

        <form wire:submit="save" class="space-y-5">

            {{-- Nombre --}}
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">
                    Nombre Completo <span class="text-rose-400">*</span>
                </label>
                <input type="text" wire:model="nombre"
                       class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                @error('nombre') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
            </div>

            {{-- Email --}}
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">
                    Email <span class="text-rose-400">*</span>
                </label>
                <input type="email" wire:model="email"
                       class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                @error('email') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
            </div>

            {{-- Teléfono --}}
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">
                    Teléfono
                </label>
                <input type="text" wire:model="telefono" placeholder="+52 55 1234 5678"
                       class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                @error('telefono') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
            </div>

            {{-- Servicios --}}
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">
                    Servicios que puede realizar <span class="text-rose-400">*</span>
                </label>
                <div class="max-h-64 overflow-y-auto rounded-xl border border-slate-700 bg-slate-900/40 p-4 space-y-2">
                    @if($availableServices->count() > 0)
                        @foreach($availableServices as $service)
                            <label class="flex cursor-pointer items-start gap-3 rounded-lg p-2 transition hover:bg-slate-800/40">
                                <input type="checkbox" wire:model="selectedServices" value="{{ $service->id }}"
                                       class="mt-0.5 h-4 w-4 rounded border-slate-600 bg-slate-900 text-emerald-600 focus:ring-emerald-500 focus:ring-offset-slate-900">
                                <div>
                                    <span class="text-sm font-medium text-slate-200">{{ $service->nombre }}</span>
                                    <p class="text-xs text-slate-500">${{ number_format($service->precio, 2) }} • {{ $service->duracion_minutos }} min</p>
                                </div>
                            </label>
                        @endforeach
                    @else
                        <p class="text-sm text-slate-500">
                            No hay servicios activos.
                            <a href="{{ route('business.services.create') }}" class="text-emerald-400 hover:text-emerald-300">Crear uno ahora</a>
                        </p>
                    @endif
                </div>
                @error('selectedServices') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                @if(count($selectedServices) > 0)
                    <p class="mt-1.5 text-xs text-slate-500">
                        {{ count($selectedServices) }} servicio(s) seleccionado(s)
                    </p>
                @endif
            </div>

            {{-- Buttons --}}
            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button" wire:click="cancel"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-700 px-4 py-2 text-sm font-medium text-slate-400 transition hover:bg-slate-800 hover:text-white">
                    Cancelar
                </button>
                <button type="submit"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:opacity-60">
                    <span wire:loading.remove>{{ $employeeId ? 'Actualizar' : 'Crear' }} Empleado</span>
                    <span wire:loading>Guardando...</span>
                </button>
            </div>
        </form>
    </div>
</div>
