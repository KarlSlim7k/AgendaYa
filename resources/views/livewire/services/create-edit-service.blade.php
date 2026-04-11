<div class="space-y-6">

    <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-6">
        <h2 class="mb-6 text-sm font-semibold uppercase tracking-widest text-slate-400">
            {{ $serviceId ? 'Editar Servicio' : 'Nuevo Servicio' }}
        </h2>

        <form wire:submit="save" class="space-y-5">

            {{-- Nombre --}}
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">
                    Nombre <span class="text-rose-400">*</span>
                </label>
                <input type="text" wire:model="nombre"
                       class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                @error('nombre') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
            </div>

            {{-- Descripción --}}
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">
                    Descripción
                </label>
                <textarea wire:model="descripcion" rows="3"
                          class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"></textarea>
                @error('descripcion') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
            </div>

            {{-- Precio y Duración --}}
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">
                        Precio (MXN) <span class="text-rose-400">*</span>
                    </label>
                    <input type="number" step="0.01" wire:model="precio"
                           class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    @error('precio') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">
                        Duración <span class="text-rose-400">*</span>
                    </label>
                    <select wire:model="duracion_minutos"
                            class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        <option value="15" class="bg-slate-900">15 minutos</option>
                        <option value="30" class="bg-slate-900">30 minutos</option>
                        <option value="45" class="bg-slate-900">45 minutos</option>
                        <option value="60" class="bg-slate-900">1 hora</option>
                        <option value="90" class="bg-slate-900">1.5 horas</option>
                        <option value="120" class="bg-slate-900">2 horas</option>
                        <option value="180" class="bg-slate-900">3 horas</option>
                    </select>
                    @error('duracion_minutos') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Buffers --}}
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">
                        Buffer Pre-cita (min)
                    </label>
                    <input type="number" min="0" max="120" wire:model="buffer_pre_minutos"
                           class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    <p class="mt-1 text-xs text-slate-600">Tiempo de preparación antes de la cita</p>
                    @error('buffer_pre_minutos') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">
                        Buffer Post-cita (min)
                    </label>
                    <input type="number" min="0" max="120" wire:model="buffer_post_minutos"
                           class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    <p class="mt-1 text-xs text-slate-600">Tiempo de limpieza después de la cita</p>
                    @error('buffer_post_minutos') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Opciones --}}
            <div class="space-y-3 rounded-xl border border-slate-800 bg-slate-800/20 p-4">
                <label class="flex cursor-pointer items-center gap-3">
                    <input type="checkbox" wire:model="requiere_confirmacion"
                           class="h-4 w-4 rounded border-slate-600 bg-slate-900 text-emerald-600 focus:ring-emerald-500 focus:ring-offset-slate-900">
                    <div>
                        <span class="text-sm font-medium text-slate-200">Requiere confirmación manual</span>
                        <p class="text-xs text-slate-500">Las citas deben ser aprobadas por el negocio</p>
                    </div>
                </label>

                <label class="flex cursor-pointer items-center gap-3">
                    <input type="checkbox" wire:model="activo"
                           class="h-4 w-4 rounded border-slate-600 bg-slate-900 text-emerald-600 focus:ring-emerald-500 focus:ring-offset-slate-900">
                    <div>
                        <span class="text-sm font-medium text-slate-200">Servicio activo</span>
                        <p class="text-xs text-slate-500">Los clientes pueden agendar este servicio</p>
                    </div>
                </label>
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
                    <span wire:loading.remove>{{ $serviceId ? 'Actualizar' : 'Crear' }} Servicio</span>
                    <span wire:loading>Guardando...</span>
                </button>
            </div>
        </form>
    </div>
</div>
