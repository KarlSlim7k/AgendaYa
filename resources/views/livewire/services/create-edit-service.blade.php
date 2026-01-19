<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h2 class="text-2xl font-semibold text-gray-800 dark:text-gray-200 mb-6">
                {{ $serviceId ? 'Editar Servicio' : 'Nuevo Servicio' }}
            </h2>

            <form wire:submit="save">
                <!-- Nombre -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Nombre <span class="text-red-600">*</span>
                    </label>
                    <input type="text" wire:model="nombre" 
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                    @error('nombre') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <!-- Descripción -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Descripción
                    </label>
                    <textarea wire:model="descripcion" rows="3"
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300"></textarea>
                    @error('descripcion') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <!-- Precio y Duración -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Precio <span class="text-red-600">*</span>
                        </label>
                        <input type="number" step="0.01" wire:model="precio" 
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                        @error('precio') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Duración (minutos) <span class="text-red-600">*</span>
                        </label>
                        <select wire:model="duracion_minutos" 
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                            <option value="15">15 minutos</option>
                            <option value="30">30 minutos</option>
                            <option value="45">45 minutos</option>
                            <option value="60">1 hora</option>
                            <option value="90">1.5 horas</option>
                            <option value="120">2 horas</option>
                            <option value="180">3 horas</option>
                        </select>
                        @error('duracion_minutos') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Buffers -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Buffer Pre-cita (minutos)
                        </label>
                        <input type="number" min="0" max="120" wire:model="buffer_pre_minutos" 
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                        <p class="text-xs text-gray-500 mt-1">Tiempo de preparación antes de la cita</p>
                        @error('buffer_pre_minutos') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Buffer Post-cita (minutos)
                        </label>
                        <input type="number" min="0" max="120" wire:model="buffer_post_minutos" 
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                        <p class="text-xs text-gray-500 mt-1">Tiempo de limpieza después de la cita</p>
                        @error('buffer_post_minutos') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Opciones -->
                <div class="mb-6 space-y-3">
                    <label class="flex items-center">
                        <input type="checkbox" wire:model="requiere_confirmacion" 
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Requiere confirmación manual</span>
                    </label>

                    <label class="flex items-center">
                        <input type="checkbox" wire:model="activo" 
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Servicio activo</span>
                    </label>
                </div>

                <!-- Buttons -->
                <div class="flex justify-end space-x-3">
                    <button type="button" wire:click="cancel" 
                        class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">
                        Cancelar
                    </button>
                    <button type="submit" 
                        class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        {{ $serviceId ? 'Actualizar' : 'Crear' }} Servicio
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
