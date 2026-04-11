<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-bold text-white">Servicios</h2>
            <p class="text-sm text-slate-400">Gestiona los servicios de tu negocio</p>
        </div>
        <a href="{{ route('business.services.create') }}"
           class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nuevo Servicio
        </a>
    </div>

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

    {{-- Filters --}}
    <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-6">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Buscar</label>
                <input type="text" wire:model.live="search" placeholder="Nombre o descripción..."
                       class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Estado</label>
                <select wire:model.live="filterActivo"
                        class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    <option value="" class="bg-slate-900">Todos</option>
                    <option value="1" class="bg-slate-900">Activos</option>
                    <option value="0" class="bg-slate-900">Inactivos</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900/60">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-slate-800">
                        <th class="px-6 py-3 text-left text-[10px] font-bold uppercase tracking-widest text-slate-500">Servicio</th>
                        <th class="px-6 py-3 text-left text-[10px] font-bold uppercase tracking-widest text-slate-500">Precio</th>
                        <th class="px-6 py-3 text-left text-[10px] font-bold uppercase tracking-widest text-slate-500">Duración</th>
                        <th class="px-6 py-3 text-left text-[10px] font-bold uppercase tracking-widest text-slate-500">Estado</th>
                        <th class="px-6 py-3 text-right text-[10px] font-bold uppercase tracking-widest text-slate-500">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($services as $service)
                        <tr wire:key="service-{{ $service->id }}" class="border-b border-slate-800/60 transition hover:bg-slate-800/30">
                            <td class="px-6 py-4">
                                <p class="text-sm font-semibold text-white">{{ $service->nombre }}</p>
                                @if($service->descripcion)
                                    <p class="text-xs text-slate-500">{{ Str::limit($service->descripcion, 50) }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm font-semibold text-emerald-400">
                                ${{ number_format($service->precio, 2) }}
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-300">
                                {{ $service->duracion_minutos }} min
                            </td>
                            <td class="px-6 py-4">
                                <button wire:click="toggleActivo({{ $service->id }})"
                                        class="rounded-full px-2.5 py-0.5 text-[10px] font-bold transition
                                               {{ $service->activo
                                                   ? 'bg-emerald-500/15 text-emerald-300 ring-1 ring-emerald-500/30 hover:bg-emerald-500/25'
                                                   : 'bg-slate-700/40 text-slate-400 ring-1 ring-slate-600/30 hover:bg-slate-700/60' }}">
                                    {{ $service->activo ? 'Activo' : 'Inactivo' }}
                                </button>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button wire:click="viewDetails({{ $service->id }})"
                                        class="text-xs font-semibold text-emerald-400 transition hover:text-emerald-300 mr-3">
                                    Ver
                                </button>
                                <a href="{{ route('business.services.edit', $service->id) }}"
                                   class="text-xs font-semibold text-blue-400 transition hover:text-blue-300 mr-3">
                                    Editar
                                </a>
                                <button wire:click="delete({{ $service->id }})"
                                        wire:confirm="¿Estás seguro de eliminar este servicio?"
                                        class="text-xs font-semibold text-rose-400 transition hover:text-rose-300">
                                    Eliminar
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-14 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="rounded-full bg-slate-800/60 p-5">
                                        <svg class="h-10 w-10 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                        </svg>
                                    </div>
                                    <p class="mt-4 text-sm font-semibold text-slate-300">No se encontraron servicios</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-800 px-6 py-3">
            {{ $services->links() }}
        </div>
    </div>

    {{-- Loading Indicator --}}
    <div wire:loading class="fixed top-4 right-4 z-50 rounded-lg bg-emerald-600/90 px-4 py-2 text-sm font-medium text-white shadow-lg backdrop-blur">
        Cargando...
    </div>

    {{-- Detail Modal --}}
    @if($showModal && $selectedService)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm"
             wire:click.self="closeModal">
            <div class="relative w-full max-w-2xl rounded-xl border border-slate-800 bg-slate-900 p-6 shadow-2xl mx-4">
                <div class="mb-5 flex items-center justify-between">
                    <h3 class="font-bold text-white">Detalles del Servicio</h3>
                    <button wire:click="closeModal"
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-800 hover:text-white">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="space-y-3">
                    <div class="rounded-lg bg-slate-800/40 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nombre</p>
                        <p class="mt-0.5 text-sm font-semibold text-white">{{ $selectedService->nombre }}</p>
                    </div>

                    @if($selectedService->descripcion)
                        <div class="rounded-lg bg-slate-800/40 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Descripción</p>
                            <p class="mt-0.5 text-sm text-slate-300">{{ $selectedService->descripcion }}</p>
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-lg bg-slate-800/40 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Precio</p>
                            <p class="mt-0.5 text-sm font-bold text-emerald-400">${{ number_format($selectedService->precio, 2) }}</p>
                        </div>
                        <div class="rounded-lg bg-slate-800/40 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Duración</p>
                            <p class="mt-0.5 text-sm font-semibold text-white">{{ $selectedService->duracion_minutos }} minutos</p>
                        </div>
                        <div class="rounded-lg bg-slate-800/40 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Buffer Pre</p>
                            <p class="mt-0.5 text-sm text-slate-300">{{ $selectedService->buffer_pre_minutos }} min</p>
                        </div>
                        <div class="rounded-lg bg-slate-800/40 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Buffer Post</p>
                            <p class="mt-0.5 text-sm text-slate-300">{{ $selectedService->buffer_post_minutos }} min</p>
                        </div>
                    </div>

                    <div class="rounded-lg bg-slate-800/40 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Estado</p>
                        <span class="mt-1 inline-block rounded-full px-2.5 py-0.5 text-[10px] font-bold
                            {{ $selectedService->activo ? 'bg-emerald-500/15 text-emerald-300 ring-1 ring-emerald-500/30' : 'bg-slate-700/40 text-slate-400 ring-1 ring-slate-600/30' }}">
                            {{ $selectedService->activo ? 'Activo' : 'Inactivo' }}
                        </span>
                    </div>

                    @if($selectedService->employees->count() > 0)
                        <div class="rounded-lg bg-slate-800/40 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Empleados asignados</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($selectedService->employees as $employee)
                                    <span class="rounded-full bg-blue-500/15 px-2.5 py-0.5 text-xs font-medium text-blue-300 ring-1 ring-blue-500/20">
                                        {{ $employee->nombre }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div class="mt-5 flex justify-end">
                    <button wire:click="closeModal"
                            class="inline-flex items-center gap-2 rounded-lg border border-slate-700 px-4 py-2 text-sm font-medium text-slate-400 transition hover:bg-slate-800 hover:text-white">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    @endif

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
                <p class="text-sm text-slate-400 mb-5">¿Estás seguro de que deseas eliminar este servicio? Esta acción no se puede deshacer.</p>
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
