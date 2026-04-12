<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-bold text-white">Gestión de Clientes</h2>
            <p class="text-sm text-slate-400">Clientes que han reservado citas en tu negocio</p>
        </div>
    </div>

    {{-- Search --}}
    <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-6">
        <label for="search" class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Buscar cliente</label>
        <input type="text"
               wire:model.live.debounce.300ms="search"
               id="search"
               placeholder="Nombre, email o teléfono..."
               class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900/60">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-slate-800">
                        <th wire:click="sortByField('nombre')" class="px-6 py-3 text-left text-[10px] font-bold uppercase tracking-widest text-slate-500 cursor-pointer hover:text-emerald-400 transition">
                            <div class="flex items-center gap-1">
                                <span>Cliente</span>
                                @if($sortBy === 'nombre')
                                    <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-[10px] font-bold uppercase tracking-widest text-slate-500">Contacto</th>
                        <th wire:click="sortByField('total_appointments')" class="px-6 py-3 text-left text-[10px] font-bold uppercase tracking-widest text-slate-500 cursor-pointer hover:text-emerald-400 transition">
                            <div class="flex items-center gap-1">
                                <span>Total Citas</span>
                                @if($sortBy === 'total_appointments')
                                    <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th wire:click="sortByField('last_appointment')" class="px-6 py-3 text-left text-[10px] font-bold uppercase tracking-widest text-slate-500 cursor-pointer hover:text-emerald-400 transition">
                            <div class="flex items-center gap-1">
                                <span>Última Cita</span>
                                @if($sortBy === 'last_appointment')
                                    <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-right text-[10px] font-bold uppercase tracking-widest text-slate-500">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clients as $client)
                        <tr wire:key="client-{{ $client->id }}" class="border-b border-slate-800/60 transition hover:bg-slate-800/30">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-500/20 text-sm font-bold text-blue-300 ring-1 ring-blue-500/20">
                                        {{ strtoupper(substr(($client->nombre ?? 'U') . ($hasApellidos ? ($client->apellidos ?? '') : ''), 0, 2)) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-white">
                                            {{ trim(($client->nombre ?? '') . ($hasApellidos ? ' ' . ($client->apellidos ?? '') : '')) }}
                                        </p>
                                        <p class="text-xs text-slate-500">Cliente desde {{ \Carbon\Carbon::parse($client->created_at)->format('M Y') }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm text-slate-300">{{ $client->email }}</p>
                                @if($client->telefono)
                                    <p class="text-xs text-slate-500">{{ $client->telefono }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="rounded-full bg-emerald-500/15 px-2.5 py-0.5 text-xs font-semibold text-emerald-300 ring-1 ring-emerald-500/20">
                                    {{ $client->total_appointments ?? 0 }} citas
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-300">
                                @if($client->last_appointment)
                                    {{ \Carbon\Carbon::parse($client->last_appointment)->format('d/m/Y') }}
                                @else
                                    <span class="text-slate-500">Sin citas</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button wire:click="viewDetail({{ $client->id }})"
                                        class="text-xs font-semibold text-emerald-400 transition hover:text-emerald-300">
                                    Ver historial
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-14 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="rounded-full bg-slate-800/60 p-5">
                                        <svg class="h-10 w-10 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </div>
                                    <p class="mt-4 text-sm font-semibold text-slate-300">No se encontraron clientes</p>
                                    <p class="mt-1 text-xs text-slate-500">Los clientes aparecerán aquí cuando reserven citas.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($clients->hasPages())
            <div class="border-t border-slate-800 px-6 py-3">
                {{ $clients->links() }}
            </div>
        @endif
    </div>

    {{-- Detail Modal --}}
    @if($showDetailModal && $selectedClient)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm"
             wire:click.self="closeDetailModal">
            <div class="relative w-full max-w-2xl rounded-xl border border-slate-800 bg-slate-900 p-6 shadow-2xl mx-4 max-h-[90vh] overflow-y-auto">

                <div class="mb-5 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-blue-500/20 text-base font-bold text-blue-300 ring-1 ring-blue-500/20">
                            {{ strtoupper(substr(($selectedClient->nombre ?? 'U') . ($hasApellidos ? ($selectedClient->apellidos ?? '') : ''), 0, 2)) }}
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">
                                {{ trim(($selectedClient->nombre ?? '') . ($hasApellidos ? ' ' . ($selectedClient->apellidos ?? '') : '')) }}
                            </h3>
                            <p class="text-sm text-slate-400">{{ $selectedClient->email }}</p>
                        </div>
                    </div>
                    <button wire:click="closeDetailModal"
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-800 hover:text-white">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Client Info --}}
                <div class="mb-6 grid grid-cols-2 gap-4 rounded-lg bg-slate-800/40 p-4">
                    @if($selectedClient->telefono)
                        <div>
                            <p class="text-xs text-slate-500 uppercase">Teléfono</p>
                            <p class="text-sm font-medium text-white">{{ $selectedClient->telefono }}</p>
                        </div>
                    @endif
                    <div>
                        <p class="text-xs text-slate-500 uppercase">Total de citas</p>
                        <p class="text-sm font-medium text-emerald-300">{{ count($clientAppointments) }}</p>
                    </div>
                </div>

                {{-- Appointment History --}}
                <div>
                    <h4 class="mb-3 text-sm font-semibold uppercase tracking-widest text-slate-400">Historial de Citas</h4>
                    @if(count($clientAppointments) > 0)
                        <div class="space-y-2">
                            @foreach($clientAppointments as $appointment)
                                <div class="flex items-center justify-between rounded-lg bg-slate-800/40 px-4 py-3">
                                    <div class="flex-1">
                                        <p class="text-sm font-semibold text-white">{{ $appointment['service']['nombre'] ?? 'Servicio eliminado' }}</p>
                                        <p class="text-xs text-slate-400">
                                            con {{ $appointment['employee']['nombre'] ?? 'Empleado eliminado' }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-bold text-white">
                                            {{ \Carbon\Carbon::parse($appointment['fecha_hora_inicio'])->format('d/m/Y H:i') }}
                                        </p>
                                        @php
                                            $statusColors = [
                                                'pending' => 'bg-amber-500/15 text-amber-200',
                                                'confirmed' => 'bg-blue-500/15 text-blue-200',
                                                'completed' => 'bg-emerald-500/15 text-emerald-200',
                                                'cancelled' => 'bg-rose-500/15 text-rose-200',
                                            ];
                                            $statusLabels = [
                                                'pending' => 'Pendiente',
                                                'confirmed' => 'Confirmada',
                                                'completed' => 'Completada',
                                                'cancelled' => 'Cancelada',
                                            ];
                                        @endphp
                                        <span class="inline-block mt-1 rounded-full px-2 py-0.5 text-[10px] font-bold {{ $statusColors[$appointment['estado']] ?? 'bg-slate-700 text-slate-400' }}">
                                            {{ $statusLabels[$appointment['estado']] ?? $appointment['estado'] }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-center text-sm text-slate-500 py-6">No hay citas registradas</p>
                    @endif
                </div>

                <div class="mt-5 flex justify-end">
                    <button wire:click="closeDetailModal"
                            class="inline-flex items-center gap-2 rounded-lg border border-slate-700 px-4 py-2 text-sm font-medium text-slate-400 transition hover:bg-slate-800 hover:text-white">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Loading --}}
    <div wire:loading class="fixed top-4 right-4 z-50 rounded-lg bg-emerald-600/90 px-4 py-2 text-sm font-medium text-white shadow-lg backdrop-blur">
        Cargando...
    </div>
</div>
