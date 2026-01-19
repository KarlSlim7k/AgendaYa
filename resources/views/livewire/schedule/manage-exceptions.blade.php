<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-gray-200 mb-6">Gestión de Horarios</h2>

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

        <!-- Tabs -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
            <div class="border-b border-gray-200 dark:border-gray-700">
                <div class="flex">
                    <a href="{{ route('schedules.index') }}" class="px-6 py-3 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent">
                        Horarios Base
                    </a>
                    <button class="px-6 py-3 text-sm font-medium text-indigo-600 border-b-2 border-indigo-600">
                        Excepciones
                    </button>
                </div>
            </div>

            <div class="p-6">
                @if($locations->count() > 0)
                    <!-- Location Selector & Add Button -->
                    <div class="mb-6 flex justify-between items-center">
                        <div class="w-1/2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Sucursal</label>
                            <select wire:model.live="selectedLocationId" 
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                @foreach($locations as $location)
                                    <option value="{{ $location->id }}">{{ $location->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if(!$showForm)
                            <button wire:click="showCreateForm" 
                                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                                + Nueva Excepción
                            </button>
                        @endif
                    </div>

                    <!-- Form -->
                    @if($showForm)
                        <div class="mb-6 border border-gray-200 dark:border-gray-700 rounded-lg p-6 bg-gray-50 dark:bg-gray-900">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">
                                {{ $exceptionId ? 'Editar Excepción' : 'Nueva Excepción' }}
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tipo</label>
                                    <select wire:model="tipo" 
                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                        <option value="feriado">Feriado</option>
                                        <option value="vacaciones">Vacaciones</option>
                                        <option value="cierre">Cierre Temporal</option>
                                    </select>
                                    @error('tipo') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Motivo</label>
                                    <input type="text" wire:model="motivo" placeholder="Ej: Día de Año Nuevo"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                    @error('motivo') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Fecha Inicio</label>
                                    <input type="date" wire:model="fecha_inicio" 
                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                    @error('fecha_inicio') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Fecha Fin</label>
                                    <input type="date" wire:model="fecha_fin" 
                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                    @error('fecha_fin') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="flex justify-end space-x-3">
                                <button wire:click="cancelForm" type="button"
                                    class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">
                                    Cancelar
                                </button>
                                <button wire:click="save" type="button"
                                    class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                                    {{ $exceptionId ? 'Actualizar' : 'Crear' }}
                                </button>
                            </div>
                        </div>
                    @endif

                    <!-- Exceptions List -->
                    @if($selectedLocationId)
                        <div class="bg-white dark:bg-gray-800 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tipo</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Motivo</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Fecha Inicio</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Fecha Fin</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        @forelse($exceptions as $exception)
                                            <tr wire:key="exception-{{ $exception->id }}">
                                                <td class="px-6 py-4">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                        {{ $exception->tipo === 'feriado' ? 'bg-purple-100 text-purple-800' : '' }}
                                                        {{ $exception->tipo === 'vacaciones' ? 'bg-blue-100 text-blue-800' : '' }}
                                                        {{ $exception->tipo === 'cierre' ? 'bg-red-100 text-red-800' : '' }}">
                                                        {{ ucfirst($exception->tipo) }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                                    {{ $exception->motivo }}
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                                    {{ $exception->fecha_inicio->format('d/m/Y') }}
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                                    {{ $exception->fecha_fin->format('d/m/Y') }}
                                                </td>
                                                <td class="px-6 py-4 text-right text-sm font-medium">
                                                    <button wire:click="edit({{ $exception->id }})" class="text-blue-600 hover:text-blue-900 mr-3">
                                                        Editar
                                                    </button>
                                                    <button wire:click="delete({{ $exception->id }})" wire:confirm="¿Eliminar esta excepción?" class="text-red-600 hover:text-red-900">
                                                        Eliminar
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                                    No hay excepciones configuradas.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                                {{ $exceptions->links() }}
                            </div>
                        </div>
                    @endif
                @else
                    <div class="text-center py-8">
                        <p class="text-gray-500 dark:text-gray-400 mb-4">No hay sucursales configuradas</p>
                        <a href="{{ route('dashboard') }}" class="text-indigo-600 hover:text-indigo-800">
                            Ir al Dashboard
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Loading Indicator -->
        <div wire:loading class="fixed top-4 right-4 bg-indigo-600 text-white px-4 py-2 rounded-md shadow-lg">
            Cargando...
        </div>
    </div>

    <!-- Modal de confirmación -->
    @if($confirmingDeletion)
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Confirmar eliminación</h3>
            <p class="text-sm text-gray-500 mb-6">¿Estás seguro de que deseas eliminar esta excepción? Esta acción no se puede deshacer.</p>
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

    <!-- Modal de confirmación de eliminación -->
    @if($confirmingDeletion)
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Confirmar eliminación</h3>
            <p class="text-sm text-gray-500 mb-6">¿Estás seguro de que deseas eliminar esta excepción? Esta acción no se puede deshacer.</p>
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
</div>
