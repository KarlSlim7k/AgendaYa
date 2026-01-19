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
                    <button class="px-6 py-3 text-sm font-medium text-indigo-600 border-b-2 border-indigo-600">
                        Horarios Base
                    </button>
                    <a href="{{ route('schedules.exceptions') }}" class="px-6 py-3 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent">
                        Excepciones
                    </a>
                </div>
            </div>

            <div class="p-6">
                @if($locations->count() > 0)
                    <!-- Location Selector -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Sucursal</label>
                        <select wire:model.live="selectedLocationId" 
                            class="w-full md:w-1/2 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}">{{ $location->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Schedule Grid -->
                    @if($selectedLocationId)
                        <div class="space-y-4">
                            @foreach($dias as $dia => $nombreDia)
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4" wire:key="day-{{ $dia }}">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-4 flex-1">
                                            <div class="w-32">
                                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $nombreDia }}</span>
                                            </div>

                                            @if($templates[$dia]['activo'])
                                                <div class="flex items-center space-x-4">
                                                    <div>
                                                        <label class="text-xs text-gray-500 dark:text-gray-400">Apertura</label>
                                                        <input type="time" wire:model="templates.{{ $dia }}.hora_apertura"
                                                            class="block w-32 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 text-sm">
                                                    </div>
                                                    <span class="text-gray-500">-</span>
                                                    <div>
                                                        <label class="text-xs text-gray-500 dark:text-gray-400">Cierre</label>
                                                        <input type="time" wire:model="templates.{{ $dia }}.hora_cierre"
                                                            class="block w-32 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 text-sm">
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-gray-400 text-sm">Cerrado</span>
                                            @endif
                                        </div>

                                        <button wire:click="toggleActivo({{ $dia }})" type="button"
                                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $templates[$dia]['activo'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                            {{ $templates[$dia]['activo'] ? 'Activo' : 'Inactivo' }}
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Save Button -->
                        <div class="mt-6 flex justify-end">
                            <button wire:click="saveAll" type="button"
                                class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                                Guardar Todos los Horarios
                            </button>
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
            Guardando...
        </div>
    </div>
</div>
