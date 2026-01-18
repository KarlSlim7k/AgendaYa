<div class="space-y-6">
    <!-- Encabezado con acciones -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Gestión de Citas</h2>
            <p class="mt-1 text-sm text-gray-600">Lista completa de citas del negocio</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('appointments.create') }}" 
               class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nueva Cita
            </a>
        </div>
    </div>

    <!-- Panel de Filtros -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Búsqueda -->
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Buscar cliente</label>
                <input type="text" 
                       wire:model.live.debounce.300ms="search" 
                       id="search"
                       placeholder="Nombre o email..."
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>

            <!-- Filtro Estado -->
            <div>
                <label for="estadoFilter" class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                <select wire:model.live="estadoFilter" 
                        id="estadoFilter"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">Todos los estados</option>
                    @foreach($estados as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Filtro Servicio -->
            <div>
                <label for="servicioFilter" class="block text-sm font-medium text-gray-700 mb-2">Servicio</label>
                <select wire:model.live="servicioFilter" 
                        id="servicioFilter"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">Todos los servicios</option>
                    @foreach($servicios as $servicio)
                        <option value="{{ $servicio->id }}">{{ $servicio->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Filtro Empleado -->
            <div>
                <label for="empleadoFilter" class="block text-sm font-medium text-gray-700 mb-2">Empleado</label>
                <select wire:model.live="empleadoFilter" 
                        id="empleadoFilter"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">Todos los empleados</option>
                    @foreach($empleados as $empleado)
                        <option value="{{ $empleado->id }}">{{ $empleado->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Fecha Desde -->
            <div>
                <label for="fechaDesde" class="block text-sm font-medium text-gray-700 mb-2">Desde</label>
                <input type="date" 
                       wire:model.live="fechaDesde" 
                       id="fechaDesde"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>

            <!-- Fecha Hasta -->
            <div>
                <label for="fechaHasta" class="block text-sm font-medium text-gray-700 mb-2">Hasta</label>
                <input type="date" 
                       wire:model.live="fechaHasta" 
                       id="fechaHasta"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>

            <!-- Botón Limpiar -->
            <div class="flex items-end md:col-span-2">
                <button wire:click="resetFilters" 
                        type="button"
                        class="w-full px-4 py-2 bg-gray-200 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 focus:outline-none focus:border-gray-400 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                    Limpiar Filtros
                </button>
            </div>
        </div>
    </div>

    <!-- Tabla de Citas -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Cliente
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Servicio
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Empleado
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Fecha y Hora
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Estado
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($appointments as $appointment)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                            <span class="text-indigo-600 font-medium text-sm">
                                                {{ strtoupper(substr($appointment->user->name, 0, 2)) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $appointment->user->name }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ $appointment->user->email }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $appointment->service->nombre }}</div>
                                <div class="text-sm text-gray-500">{{ $appointment->service->duracion_minutos }} min</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $appointment->employee->nombre }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    {{ $appointment->fecha_hora_inicio->format('d/m/Y') }}
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ $appointment->fecha_hora_inicio->format('H:i') }} - 
                                    {{ $appointment->fecha_hora_fin->format('H:i') }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $statusClasses = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'confirmed' => 'bg-blue-100 text-blue-800',
                                        'completed' => 'bg-green-100 text-green-800',
                                        'cancelled' => 'bg-red-100 text-red-800',
                                        'no_show' => 'bg-gray-100 text-gray-800',
                                    ];
                                @endphp
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusClasses[$appointment->estado] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ $estados[$appointment->estado] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button wire:click="viewDetail({{ $appointment->id }})" 
                                        class="text-indigo-600 hover:text-indigo-900 mr-3">
                                    Ver
                                </button>
                                @if(in_array($appointment->estado, ['pending', 'confirmed']))
                                    <button wire:click="cancelAppointment({{ $appointment->id }})" 
                                            wire:confirm="¿Está seguro de cancelar esta cita?"
                                            class="text-red-600 hover:text-red-900">
                                        Cancelar
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No hay citas</h3>
                                <p class="mt-1 text-sm text-gray-500">No se encontraron citas con los filtros seleccionados.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        @if($appointments->hasPages())
            <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                {{ $appointments->links() }}
            </div>
        @endif
    </div>

    <!-- Modal de Detalle (si existe cita seleccionada) -->
    @if($showDetailModal && $selectedAppointment)
        <div class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Overlay -->
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                     wire:click="closeDetailModal"></div>

                <!-- Centrar modal -->
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <!-- Panel del modal -->
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">
                                    Detalle de Cita #{{ $selectedAppointment->id }}
                                </h3>
                                
                                <dl class="space-y-3">
                                    <div class="flex justify-between">
                                        <dt class="text-sm font-medium text-gray-500">Cliente:</dt>
                                        <dd class="text-sm text-gray-900">{{ $selectedAppointment->user->name }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm font-medium text-gray-500">Email:</dt>
                                        <dd class="text-sm text-gray-900">{{ $selectedAppointment->user->email }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm font-medium text-gray-500">Servicio:</dt>
                                        <dd class="text-sm text-gray-900">{{ $selectedAppointment->service->nombre }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm font-medium text-gray-500">Empleado:</dt>
                                        <dd class="text-sm text-gray-900">{{ $selectedAppointment->employee->nombre }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm font-medium text-gray-500">Fecha:</dt>
                                        <dd class="text-sm text-gray-900">{{ $selectedAppointment->fecha_hora_inicio->format('d/m/Y H:i') }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm font-medium text-gray-500">Duración:</dt>
                                        <dd class="text-sm text-gray-900">{{ $selectedAppointment->service->duracion_minutos }} minutos</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm font-medium text-gray-500">Estado:</dt>
                                        <dd class="text-sm text-gray-900">{{ $estados[$selectedAppointment->estado] }}</dd>
                                    </div>
                                    @if($selectedAppointment->notas_cliente)
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500 mb-1">Notas del cliente:</dt>
                                            <dd class="text-sm text-gray-900 bg-gray-50 p-2 rounded">{{ $selectedAppointment->notas_cliente }}</dd>
                                        </div>
                                    @endif
                                    @if($selectedAppointment->notas_internas)
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500 mb-1">Notas internas:</dt>
                                            <dd class="text-sm text-gray-900 bg-yellow-50 p-2 rounded">{{ $selectedAppointment->notas_internas }}</dd>
                                        </div>
                                    @endif
                                </dl>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button wire:click="closeDetailModal" 
                                type="button" 
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Loading Indicator -->
    <div wire:loading class="fixed top-0 left-0 right-0 z-50">
        <div class="bg-indigo-600 text-white px-4 py-2 text-center text-sm font-medium">
            Cargando...
        </div>
    </div>
</div>
