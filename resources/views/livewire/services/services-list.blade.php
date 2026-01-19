<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6 flex justify-between items-center">
            <h2 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">Servicios</h2>
            <a href="{{ route('services.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                + Nuevo Servicio
            </a>
        </div>

        <!-- Flash Messages -->
        @if (session()->has('message'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                {{ session('message') }}
            </div>
        @endif
        @if (session()->has('error'))
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                {{ session('error') }}
            </div>
        @endif

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Buscar</label>
                    <input type="text" wire:model.live="search" placeholder="Nombre o descripción..." 
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Estado</label>
                    <select wire:model.live="filterActivo" 
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                        <option value="">Todos</option>
                        <option value="1">Activos</option>
                        <option value="0">Inactivos</option>
                    </select>
                </div>
            </div>

    <!-- Modal de confirmación -->
    @if($confirmingDeletion)
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Confirmar eliminación</h3>
            <p class="text-sm text-gray-500 mb-6">¿Estás seguro de que deseas eliminar este servicio? Esta acción no se puede deshacer.</p>
            <div class="flex justify-end space-x-3">
                <button wire:click="cancelDelete" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                    Cancelar
                </button>
                <button wire:click="delete" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                    Eliminar
                </button>
            </div>
        </div>
    </div>
    @endif
        <!-- Services List -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Servicio</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Precio</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Duración</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Estado</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($services as $service)
                            <tr wire:key="service-{{ $service->id }}">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $service->nombre }}</div>
                                    @if($service->descripcion)
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ Str::limit($service->descripcion, 50) }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    ${{ number_format($service->precio, 2) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    {{ $service->duracion_minutos }} min
                                </td>
                                <td class="px-6 py-4">
                                    <button wire:click="toggleActivo({{ $service->id }})" 
                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $service->activo ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ $service->activo ? 'Activo' : 'Inactivo' }}
                                    </button>
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium">
                                    <button wire:click="viewDetails({{ $service->id }})" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                        Ver
                                    </button>
                                    <a href="{{ route('services.edit', $service->id) }}" class="text-blue-600 hover:text-blue-900 mr-3">
                                        Editar
                                    </a>
                                    <button wire:click="delete({{ $service->id }})" wire:confirm="¿Estás seguro de eliminar este servicio?" class="text-red-600 hover:text-red-900">
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                    No se encontraron servicios.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $services->links() }}
            </div>
        </div>

        <!-- Loading Indicator -->
        <div wire:loading class="fixed top-4 right-4 bg-indigo-600 text-white px-4 py-2 rounded-md shadow-lg">
            Cargando...
        </div>

        <!-- Modal Details -->
        @if($showModal && $selectedService)
            <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: @entangle('showModal') }">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

                    <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-2xl w-full p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Detalles del Servicio</h3>
                            <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Nombre</label>
                                <p class="text-gray-900 dark:text-gray-100">{{ $selectedService->nombre }}</p>
                            </div>

                            @if($selectedService->descripcion)
                                <div>
                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Descripción</label>
                                    <p class="text-gray-900 dark:text-gray-100">{{ $selectedService->descripcion }}</p>
                                </div>
                            @endif

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Precio</label>
                                    <p class="text-gray-900 dark:text-gray-100">${{ number_format($selectedService->precio, 2) }}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Duración</label>
                                    <p class="text-gray-900 dark:text-gray-100">{{ $selectedService->duracion_minutos }} minutos</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Buffer Pre</label>
                                    <p class="text-gray-900 dark:text-gray-100">{{ $selectedService->buffer_pre_minutos }} min</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Buffer Post</label>
                                    <p class="text-gray-900 dark:text-gray-100">{{ $selectedService->buffer_post_minutos }} min</p>
                                </div>
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Estado</label>
                                <p class="text-gray-900 dark:text-gray-100">{{ $selectedService->activo ? 'Activo' : 'Inactivo' }}</p>
                            </div>

                            @if($selectedService->employees->count() > 0)
                                <div>
                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Empleados asignados</label>
                                    <ul class="mt-2 space-y-1">
                                        @foreach($selectedService->employees as $employee)
                                            <li class="text-gray-900 dark:text-gray-100">• {{ $employee->nombre }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>

                        <div class="mt-6 flex justify-end">
                            <button wire:click="closeModal" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
