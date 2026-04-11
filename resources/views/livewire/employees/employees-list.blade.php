<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-bold text-white">Empleados</h2>
            <p class="text-sm text-slate-400">Gestiona el equipo de tu negocio</p>
        </div>
        <a href="{{ route('business.employees.create') }}"
           class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
            </svg>
            Nuevo Empleado
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

    {{-- Search --}}
    <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-6">
        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Buscar</label>
        <input type="text" wire:model.live="search" placeholder="Nombre, email o teléfono..."
               class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900/60">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-slate-800">
                        <th class="px-6 py-3 text-left text-[10px] font-bold uppercase tracking-widest text-slate-500">Empleado</th>
                        <th class="px-6 py-3 text-left text-[10px] font-bold uppercase tracking-widest text-slate-500">Contacto</th>
                        <th class="px-6 py-3 text-left text-[10px] font-bold uppercase tracking-widest text-slate-500">Servicios</th>
                        <th class="px-6 py-3 text-right text-[10px] font-bold uppercase tracking-widest text-slate-500">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $employee)
                        <tr wire:key="employee-{{ $employee->id }}" class="border-b border-slate-800/60 transition hover:bg-slate-800/30">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-violet-500/20 text-sm font-bold text-violet-300 ring-1 ring-violet-500/20">
                                        {{ strtoupper(substr($employee->nombre, 0, 2)) }}
                                    </div>
                                    <p class="text-sm font-semibold text-white">{{ $employee->nombre }}</p>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm text-slate-300">{{ $employee->email }}</p>
                                @if($employee->telefono)
                                    <p class="text-xs text-slate-500">{{ $employee->telefono }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="rounded-full bg-blue-500/15 px-2.5 py-0.5 text-xs font-semibold text-blue-300 ring-1 ring-blue-500/20">
                                    {{ $employee->services->count() }} servicio(s)
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button wire:click="viewDetails({{ $employee->id }})"
                                        class="text-xs font-semibold text-emerald-400 transition hover:text-emerald-300 mr-3">
                                    Ver
                                </button>
                                <a href="{{ route('business.employees.edit', $employee->id) }}"
                                   class="text-xs font-semibold text-blue-400 transition hover:text-blue-300 mr-3">
                                    Editar
                                </a>
                                <button wire:click="confirmDelete({{ $employee->id }})"
                                        class="text-xs font-semibold text-rose-400 transition hover:text-rose-300">
                                    Eliminar
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-14 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="rounded-full bg-slate-800/60 p-5">
                                        <svg class="h-10 w-10 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </div>
                                    <p class="mt-4 text-sm font-semibold text-slate-300">No se encontraron empleados</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-800 px-6 py-3">
            {{ $employees->links() }}
        </div>
    </div>

    {{-- Loading --}}
    <div wire:loading class="fixed top-4 right-4 z-50 rounded-lg bg-emerald-600/90 px-4 py-2 text-sm font-medium text-white shadow-lg backdrop-blur">
        Cargando...
    </div>

    {{-- Detail Modal --}}
    @if($showModal && $selectedEmployee)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm"
             wire:click.self="closeModal">
            <div class="relative w-full max-w-2xl rounded-xl border border-slate-800 bg-slate-900 p-6 shadow-2xl mx-4">
                <div class="mb-5 flex items-center justify-between">
                    <h3 class="font-bold text-white">Detalles del Empleado</h3>
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
                        <p class="mt-0.5 text-sm font-semibold text-white">{{ $selectedEmployee->nombre }}</p>
                    </div>
                    <div class="rounded-lg bg-slate-800/40 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Email</p>
                        <p class="mt-0.5 text-sm text-slate-300">{{ $selectedEmployee->email }}</p>
                    </div>
                    @if($selectedEmployee->telefono)
                        <div class="rounded-lg bg-slate-800/40 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Teléfono</p>
                            <p class="mt-0.5 text-sm text-slate-300">{{ $selectedEmployee->telefono }}</p>
                        </div>
                    @endif

                    @if($selectedEmployee->services->count() > 0)
                        <div class="rounded-lg bg-slate-800/40 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Servicios asignados</p>
                            <div class="space-y-2">
                                @foreach($selectedEmployee->services as $service)
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-slate-300">{{ $service->nombre }}</span>
                                        <span class="text-xs font-semibold text-emerald-400">${{ number_format($service->precio, 2) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="rounded-lg bg-slate-800/40 px-4 py-3">
                            <p class="text-sm italic text-slate-500">No tiene servicios asignados</p>
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

    {{-- Delete Confirm --}}
    @if($confirmingDeletion)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-xl border border-slate-800 bg-slate-900 p-6 shadow-2xl mx-4">
                <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-rose-500/15 ring-1 ring-rose-500/30">
                    <svg class="h-6 w-6 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-white mb-1">Confirmar eliminación</h3>
                <p class="text-sm text-slate-400 mb-5">¿Estás seguro de que deseas eliminar este empleado? Esta acción no se puede deshacer.</p>
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
