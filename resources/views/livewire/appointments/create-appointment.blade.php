<div>
    @if($showSuccess)
        <!-- Success Message -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="flex items-center justify-center mb-6">
                    <svg class="w-16 h-16 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                
                <h3 class="text-2xl font-bold text-center text-gray-900 mb-4">
                    ¡Cita creada exitosamente!
                </h3>
                
                <div class="bg-gray-50 rounded-lg p-6 space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Código de confirmación:</span>
                        <span class="font-mono font-bold text-lg">{{ $createdAppointment->codigo_confirmacion }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Cliente:</span>
                        <span class="font-semibold">{{ $createdAppointment->user->nombre }} {{ $createdAppointment->user->apellidos }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Email:</span>
                        <span>{{ $createdAppointment->user->email }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Servicio:</span>
                        <span class="font-semibold">{{ $createdAppointment->service->nombre }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Empleado:</span>
                        <span>{{ $createdAppointment->employee->nombre }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Fecha y hora:</span>
                        <span class="font-semibold">{{ $createdAppointment->fecha_hora_inicio->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Duración:</span>
                        <span>{{ $createdAppointment->service->duracion_minutos }} minutos</span>
                    </div>
                </div>
                
                <div class="mt-6 flex gap-4">
                    <a href="{{ route('appointments.index') }}" 
                       class="flex-1 bg-gray-600 hover:bg-gray-700 text-white font-bold py-3 px-4 rounded text-center transition duration-150">
                        Ver todas las citas
                    </a>
                    <button wire:click="createAnother" 
                            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded transition duration-150">
                        Crear otra cita
                    </button>
                </div>
            </div>
        </div>
    @else
        <!-- Multi-step Form -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <!-- Progress Steps -->
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center">
                                <div class="flex items-center justify-center w-10 h-10 rounded-full {{ $currentStep >= 1 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600' }}">
                                    1
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium {{ $currentStep >= 1 ? 'text-blue-600' : 'text-gray-600' }}">
                                        Seleccionar Servicio
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex-1 border-t-2 {{ $currentStep >= 2 ? 'border-blue-600' : 'border-gray-200' }}"></div>
                        
                        <div class="flex-1">
                            <div class="flex items-center">
                                <div class="flex items-center justify-center w-10 h-10 rounded-full {{ $currentStep >= 2 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600' }}">
                                    2
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium {{ $currentStep >= 2 ? 'text-blue-600' : 'text-gray-600' }}">
                                        Fecha y Hora
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex-1 border-t-2 {{ $currentStep >= 3 ? 'border-blue-600' : 'border-gray-200' }}"></div>
                        
                        <div class="flex-1">
                            <div class="flex items-center">
                                <div class="flex items-center justify-center w-10 h-10 rounded-full {{ $currentStep >= 3 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600' }}">
                                    3
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium {{ $currentStep >= 3 ? 'text-blue-600' : 'text-gray-600' }}">
                                        Datos del Cliente
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step Content -->
                <form wire:submit.prevent="submit">
                    <!-- Step 1: Service Selection -->
                    @if($currentStep === 1)
                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Seleccione el servicio <span class="text-red-500">*</span>
                                </label>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    @forelse($services as $service)
                                        <div wire:click="$set('serviceId', {{ $service->id }})"
                                             class="relative border-2 rounded-lg p-4 cursor-pointer transition-all duration-200
                                                    {{ $serviceId == $service->id ? 'border-blue-600 bg-blue-50' : 'border-gray-200 hover:border-blue-300' }}">
                                            <div class="flex items-start justify-between">
                                                <div class="flex-1">
                                                    <h4 class="font-semibold text-gray-900">{{ $service->nombre }}</h4>
                                                    @if($service->descripcion)
                                                        <p class="text-sm text-gray-600 mt-1">{{ $service->descripcion }}</p>
                                                    @endif
                                                    <div class="flex items-center gap-4 mt-3">
                                                        <span class="text-sm text-gray-500">
                                                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                            </svg>
                                                            {{ $service->duracion_minutos }} min
                                                        </span>
                                                        <span class="text-lg font-bold text-blue-600">
                                                            ${{ number_format($service->precio, 2) }}
                                                        </span>
                                                    </div>
                                                </div>
                                                @if($serviceId == $service->id)
                                                    <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                    </svg>
                                                @endif
                                            </div>
                                        </div>
                                    @empty
                                        <div class="col-span-2 text-center py-8 text-gray-500">
                                            No hay servicios disponibles
                                        </div>
                                    @endforelse
                                </div>
                                
                                @error('serviceId')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex justify-end">
                                <button type="button" 
                                        wire:click="nextStep"
                                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded transition duration-150"
                                        {{ !$serviceId ? 'disabled' : '' }}>
                                    Siguiente →
                                </button>
                            </div>
                        </div>
                    @endif

                    <!-- Step 2: Employee, Date & Time Selection -->
                    @if($currentStep === 2)
                        <div class="space-y-6">
                            <!-- Selected Service Summary -->
                            @if($selectedService)
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                    <p class="text-sm text-gray-600">Servicio seleccionado:</p>
                                    <p class="font-semibold text-gray-900">{{ $selectedService->nombre }}</p>
                                    <p class="text-sm text-gray-600">{{ $selectedService->duracion_minutos }} min • ${{ number_format($selectedService->precio, 2) }}</p>
                                </div>
                            @endif

                            <!-- Employee Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Seleccione el empleado <span class="text-red-500">*</span>
                                </label>
                                
                                @if($loadingEmployees)
                                    <div class="text-center py-4">
                                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                                    </div>
                                @else
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        @forelse($employees as $employee)
                                            <div wire:click="$set('employeeId', {{ $employee->id }})"
                                                 class="border-2 rounded-lg p-4 cursor-pointer text-center transition-all
                                                        {{ $employeeId == $employee->id ? 'border-blue-600 bg-blue-50' : 'border-gray-200 hover:border-blue-300' }}">
                                                <div class="w-16 h-16 bg-gray-300 rounded-full mx-auto mb-2 flex items-center justify-center text-2xl font-bold text-gray-600">
                                                    {{ substr($employee->nombre, 0, 1) }}
                                                </div>
                                                <p class="font-semibold">{{ $employee->nombre }}</p>
                                                @if($employeeId == $employee->id)
                                                    <svg class="w-5 h-5 text-blue-600 mx-auto mt-2" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                    </svg>
                                                @endif
                                            </div>
                                        @empty
                                            <div class="col-span-3 text-center py-4 text-gray-500">
                                                No hay empleados disponibles para este servicio
                                            </div>
                                        @endforelse
                                    </div>
                                @endif
                                
                                @error('employeeId')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Date Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Seleccione la fecha <span class="text-red-500">*</span>
                                </label>
                                <input type="date" 
                                       wire:model.live="selectedDate"
                                       min="{{ \Carbon\Carbon::today()->format('Y-m-d') }}"
                                       max="{{ \Carbon\Carbon::today()->addMonths(2)->format('Y-m-d') }}"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @error('selectedDate')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Time Slots -->
                            @if($employeeId && $selectedDate)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Seleccione el horario <span class="text-red-500">*</span>
                                    </label>
                                    
                                    @if($loadingSlots)
                                        <div class="text-center py-8">
                                            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                                            <p class="text-sm text-gray-600 mt-2">Cargando disponibilidad...</p>
                                        </div>
                                    @else
                                        <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-2">
                                            @forelse($availableSlots as $slot)
                                                <button type="button"
                                                        wire:click="$set('selectedSlot', '{{ $slot['time'] }}')"
                                                        class="py-2 px-3 rounded text-sm font-medium transition-all
                                                               {{ $selectedSlot == $slot['time'] ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                                                    {{ $slot['display'] }}
                                                </button>
                                            @empty
                                                <div class="col-span-full text-center py-8 text-gray-500">
                                                    No hay horarios disponibles para esta fecha
                                                </div>
                                            @endforelse
                                        </div>
                                    @endif
                                    
                                    @error('selectedSlot')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endif

                            <div class="flex justify-between pt-4">
                                <button type="button" 
                                        wire:click="previousStep"
                                        class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-6 rounded transition duration-150">
                                    ← Anterior
                                </button>
                                <button type="button" 
                                        wire:click="nextStep"
                                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded transition duration-150"
                                        {{ !$employeeId || !$selectedDate || !$selectedSlot ? 'disabled' : '' }}>
                                    Siguiente →
                                </button>
                            </div>
                        </div>
                    @endif

                    <!-- Step 3: Customer Information -->
                    @if($currentStep === 3)
                        <div class="space-y-6">
                            <!-- Appointment Summary -->
                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 space-y-2">
                                <h3 class="font-semibold text-gray-900 mb-3">Resumen de la cita</h3>
                                <div class="grid grid-cols-2 gap-2 text-sm">
                                    <span class="text-gray-600">Servicio:</span>
                                    <span class="font-medium">{{ $selectedService->nombre }}</span>
                                    
                                    <span class="text-gray-600">Empleado:</span>
                                    <span class="font-medium">{{ $selectedEmployee->nombre }}</span>
                                    
                                    <span class="text-gray-600">Fecha:</span>
                                    <span class="font-medium">{{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}</span>
                                    
                                    <span class="text-gray-600">Hora:</span>
                                    <span class="font-medium">{{ \Carbon\Carbon::parse($selectedSlot)->format('H:i') }}</span>
                                    
                                    <span class="text-gray-600">Duración:</span>
                                    <span class="font-medium">{{ $selectedService->duracion_minutos }} minutos</span>
                                    
                                    <span class="text-gray-600">Precio:</span>
                                    <span class="font-medium text-blue-600">${{ number_format($selectedService->precio, 2) }}</span>
                                </div>
                            </div>

                            <!-- Customer Form -->
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Nombre completo <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" 
                                           wire:model="userName"
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                           placeholder="Ej: Juan Pérez García">
                                    @error('userName')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Email <span class="text-red-500">*</span>
                                    </label>
                                    <input type="email" 
                                           wire:model="userEmail"
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                           placeholder="ejemplo@email.com">
                                    @error('userEmail')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Teléfono
                                    </label>
                                    <input type="tel" 
                                           wire:model="userPhone"
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                           placeholder="+52 55 1234 5678">
                                    @error('userPhone')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Notas adicionales
                                    </label>
                                    <textarea wire:model="notes"
                                              rows="3"
                                              class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                              placeholder="Información adicional sobre la cita..."></textarea>
                                    @error('notes')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            @error('submit')
                                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="flex justify-between pt-4">
                                <button type="button" 
                                        wire:click="previousStep"
                                        class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-6 rounded transition duration-150">
                                    ← Anterior
                                </button>
                                <button type="submit"
                                        class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded transition duration-150 flex items-center gap-2"
                                        wire:loading.attr="disabled">
                                    <span wire:loading.remove>Crear Cita</span>
                                    <span wire:loading>
                                        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Creando...
                                    </span>
                                </button>
                            </div>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    @endif
</div>
