<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h2 class="text-2xl font-semibold text-gray-800 dark:text-gray-200 mb-6">
                {{ $employeeId ? 'Editar Empleado' : 'Nuevo Empleado' }}
            </h2>

            <form wire:submit="save">
                <!-- Nombre -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Nombre Completo <span class="text-red-600">*</span>
                    </label>
                    <input type="text" wire:model="nombre" 
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                    @error('nombre') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <!-- Email -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Email <span class="text-red-600">*</span>
                    </label>
                    <input type="email" wire:model="email" 
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                    @error('email') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <!-- Teléfono -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Teléfono
                    </label>
                    <input type="text" wire:model="telefono" placeholder="+52 55 1234 5678"
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                    @error('telefono') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <!-- Servicios -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Servicios que puede realizar <span class="text-red-600">*</span>
                    </label>
                    <div class="border border-gray-300 dark:border-gray-600 rounded-md p-4 max-h-64 overflow-y-auto">
                        @if($availableServices->count() > 0)
                            <div class="space-y-2">
                                @foreach($availableServices as $service)
                                    <label class="flex items-start">
                                        <input type="checkbox" wire:model="selectedServices" value="{{ $service->id }}"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 mt-1">
                                        <div class="ml-3">
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $service->nombre }}</span>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                ${{ number_format($service->precio, 2) }} • {{ $service->duracion_minutos }} min
                                            </div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-500 dark:text-gray-400 text-sm">
                                No hay servicios activos. <a href="{{ route('business.services.create') }}" class="text-indigo-600 hover:text-indigo-800">Crear uno ahora</a>
                            </p>
                        @endif
                    </div>
                    @error('selectedServices') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    @if(count($selectedServices) > 0)
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                            {{ count($selectedServices) }} servicio(s) seleccionado(s)
                        </p>
                    @endif
                </div>

                <!-- Buttons -->
                <div class="flex justify-end space-x-3">
                    <button type="button" wire:click="cancel" 
                        class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">
                        Cancelar
                    </button>
                    <button type="submit" 
                        class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        {{ $employeeId ? 'Actualizar' : 'Crear' }} Empleado
                    </button>
                </div>
            </form>

            <!-- Loading Indicator -->
            <div wire:loading class="fixed top-4 right-4 bg-indigo-600 text-white px-4 py-2 rounded-md shadow-lg">
                Guardando...
            </div>
        </div>
    </div>
</div>
