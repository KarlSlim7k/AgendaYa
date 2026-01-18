<div class="space-y-6">
    @if(session()->has('message'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">
            {{ session('message') }}
        </div>
    @endif

    <!-- Location Selector -->
    @if($locations->count() > 1)
        <div class="bg-white rounded-lg shadow p-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Sucursal</label>
            <select wire:model.live="selectedLocationId" class="w-full md:w-64 border-gray-300 rounded-md">
                @foreach($locations as $location)
                    <option value="{{ $location->id }}">{{ $location->nombre }}</option>
                @endforeach
            </select>
        </div>
    @endif

    <!-- Weekly Schedule -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Horario Semanal</h3>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                @foreach($schedules as $schedule)
                    <div class="flex items-center gap-4 p-4 border rounded-lg {{ $schedule['activo'] ? 'bg-white' : 'bg-gray-50' }}">
                        <div class="flex items-center w-32">
                            <input type="checkbox" 
                                   wire:click="toggleDay({{ $schedule['dia_semana'] }})"
                                   {{ $schedule['activo'] ? 'checked' : '' }}
                                   class="mr-2 rounded">
                            <span class="font-medium {{ $schedule['activo'] ? 'text-gray-900' : 'text-gray-400' }}">
                                {{ $schedule['nombre'] }}
                            </span>
                        </div>
                        
                        @if($schedule['activo'])
                            <div class="flex items-center gap-2 flex-1">
                                <input type="time" 
                                       wire:model="schedules.{{ $loop->index }}.hora_apertura"
                                       class="border-gray-300 rounded-md text-sm">
                                <span class="text-gray-500">-</span>
                                <input type="time" 
                                       wire:model="schedules.{{ $loop->index }}.hora_cierre"
                                       class="border-gray-300 rounded-md text-sm">
                                <button wire:click="saveSchedule({{ $schedule['dia_semana'] }})"
                                        class="ml-2 px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
                                    Guardar
                                </button>
                            </div>
                        @else
                            <span class="text-gray-400 text-sm">Cerrado</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Exceptions -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-900">Excepciones de Horario</h3>
            <button wire:click="openExceptionModal" 
                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                + Nueva Excepción
            </button>
        </div>
        <div class="divide-y divide-gray-200">
            @forelse($exceptions as $exception)
                <div class="p-4 hover:bg-gray-50 flex justify-between items-center">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-1 text-xs rounded {{ 
                                $exception['tipo'] === 'feriado' ? 'bg-red-100 text-red-800' : 
                                ($exception['tipo'] === 'vacaciones' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800') 
                            }}">
                                {{ ucfirst($exception['tipo']) }}
                            </span>
                            <span class="font-medium text-gray-900">{{ $exception['motivo'] }}</span>
                        </div>
                        <p class="text-sm text-gray-600 mt-1">
                            {{ \Carbon\Carbon::parse($exception['fecha_inicio'])->format('d/m/Y') }}
                            @if($exception['fecha_inicio'] !== $exception['fecha_fin'])
                                - {{ \Carbon\Carbon::parse($exception['fecha_fin'])->format('d/m/Y') }}
                            @endif
                            @if(!$exception['todo_el_dia'])
                                ({{ $exception['hora_inicio'] }} - {{ $exception['hora_fin'] }})
                            @endif
                        </p>
                    </div>
                    <button wire:click="deleteException({{ $exception['id'] }})" 
                            wire:confirm="¿Eliminar esta excepción?"
                            class="text-red-600 hover:text-red-800">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </div>
            @empty
                <div class="p-8 text-center text-gray-500">
                    No hay excepciones registradas
                </div>
            @endforelse
        </div>
    </div>

    <!-- Exception Modal -->
    @if($showExceptionModal)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Nueva Excepción</h3>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo</label>
                        <select wire:model.live="exceptionTipo" class="w-full border-gray-300 rounded-md">
                            <option value="feriado">Feriado</option>
                            <option value="vacaciones">Vacaciones</option>
                            <option value="cierre">Cierre Temporal</option>
                        </select>
                    </div>

                    @if($exceptionTipo === 'feriado')
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Fecha</label>
                            <input type="date" wire:model="exceptionFecha" class="w-full border-gray-300 rounded-md">
                            @error('exceptionFecha') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                    @else
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Inicio</label>
                                <input type="date" wire:model="exceptionFechaInicio" class="w-full border-gray-300 rounded-md">
                                @error('exceptionFechaInicio') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Fin</label>
                                <input type="date" wire:model="exceptionFechaFin" class="w-full border-gray-300 rounded-md">
                                @error('exceptionFechaFin') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    @endif

                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" wire:model.live="exceptionTodoElDia" class="rounded mr-2">
                            <span class="text-sm text-gray-700">Todo el día</span>
                        </label>
                    </div>

                    @if(!$exceptionTodoElDia)
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Hora Inicio</label>
                                <input type="time" wire:model="exceptionHoraInicio" class="w-full border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Hora Fin</label>
                                <input type="time" wire:model="exceptionHoraFin" class="w-full border-gray-300 rounded-md">
                            </div>
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Motivo</label>
                        <input type="text" wire:model="exceptionMotivo" class="w-full border-gray-300 rounded-md" placeholder="Ej: Día de la Independencia">
                        @error('exceptionMotivo') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="p-6 border-t border-gray-200 flex justify-end gap-2">
                    <button wire:click="$set('showExceptionModal', false)" 
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
                        Cancelar
                    </button>
                    <button wire:click="saveException" 
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Guardar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
