<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Nueva Cita</h2>

                @if (session()->has('error'))
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        {{ session('error') }}
                    </div>
                @endif

                <!-- Progress Steps -->
                <div class="mb-8">
                    <div class="flex justify-between items-center">
                        @foreach(['Servicio', 'Empleado', 'Fecha y Hora', 'Cliente', 'Confirmación'] as $index => $step)
                            <div class="flex-1 {{ $index < 4 ? 'mr-2' : '' }}">
                                <div class="flex items-center">
                                    <div class="flex items-center justify-center w-10 h-10 rounded-full {{ $currentStep > $index + 1 ? 'bg-green-500' : ($currentStep === $index + 1 ? 'bg-indigo-600' : 'bg-gray-300') }} text-white font-semibold">
                                        {{ $index + 1 }}
                                    </div>
                                    <div class="ml-2 text-sm font-medium {{ $currentStep === $index + 1 ? 'text-indigo-600' : 'text-gray-500' }}">
                                        {{ $step }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <form wire:submit="createAppointment">
                    <!-- Step 1: Service Selection -->
                    @if($currentStep === 1)
                        <div class="space-y-4">
                            <h3 class="text-lg font-medium text-gray-900">Selecciona un Servicio</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @foreach($services as $service)
                                    <div wire:click="$set('selectedService', {{ $service->id }})" 
                                         class="cursor-pointer border-2 rounded-lg p-4 {{ $selectedService === $service->id ? 'border-indigo-600 bg-indigo-50' : 'border-gray-200 hover:border-gray-300' }}">
                                        <h4 class="font-semibold text-gray-900">{{ $service->nombre }}</h4>
                                        <p class="text-sm text-gray-600 mt-1">{{ $service->descripcion }}</p>
                                        <div class="mt-2 flex justify-between text-sm">
                                            <span class="text-gray-500">{{ $service->duracion_minutos }} min</span>
                                            <span class="font-semibold text-indigo-600">${{ number_format($service->precio, 2) }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @error('selectedService') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    <!-- Step 2: Employee Selection -->
                    @if($currentStep === 2)
                        <div class="space-y-4">
                            <h3 class="text-lg font-medium text-gray-900">Selecciona un Empleado</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @foreach($employees as $employee)
                                    <div wire:click="$set('selectedEmployee', {{ $employee->id }})" 
                                         class="cursor-pointer border-2 rounded-lg p-4 {{ $selectedEmployee === $employee->id ? 'border-indigo-600 bg-indigo-50' : 'border-gray-200 hover:border-gray-300' }}">
                                        <h4 class="font-semibold text-gray-900">{{ $employee->nombre }}</h4>
                                        @if($employee->cargo)
                                            <p class="text-sm text-gray-600">{{ $employee->cargo }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                            @error('selectedEmployee') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    <!-- Step 3: Date and Time -->
                    @if($currentStep === 3)
                        <div class="space-y-4">
                            <h3 class="text-lg font-medium text-gray-900">Selecciona Fecha y Hora</h3>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Fecha</label>
                                <input type="date" wire:model.live="selectedDate" 
                                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('selectedDate') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            @if(count($availableSlots) > 0)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Horarios Disponibles</label>
                                    <div class="grid grid-cols-4 md:grid-cols-6 gap-2">
                                        @foreach($availableSlots as $slot)
                                            <button type="button" wire:click="$set('selectedSlot', '{{ $slot['fecha_hora_inicio'] }}')"
                                                    class="px-3 py-2 text-sm rounded-md {{ $selectedSlot === $slot['fecha_hora_inicio'] ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                                                {{ \Carbon\Carbon::parse($slot['fecha_hora_inicio'])->format('H:i') }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <p class="text-gray-500 text-sm">No hay horarios disponibles para esta fecha.</p>
                            @endif
                            @error('selectedSlot') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    <!-- Step 4: Customer Selection -->
                    @if($currentStep === 4)
                        <div class="space-y-4">
                            <h3 class="text-lg font-medium text-gray-900">Selecciona Cliente</h3>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Buscar Cliente</label>
                                <input type="text" wire:model.live="customerSearch" 
                                       placeholder="Nombre o email del cliente..."
                                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                
                                @if(count($searchResults) > 0)
                                    <div class="mt-2 border border-gray-200 rounded-md divide-y">
                                        @foreach($searchResults as $customer)
                                            <div wire:click="selectCustomer({{ $customer['id'] }})" 
                                                 class="p-3 cursor-pointer hover:bg-gray-50">
                                                <p class="font-medium text-gray-900">{{ $customer['nombre'] }}</p>
                                                <p class="text-sm text-gray-500">{{ $customer['email'] }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            @error('selectedCustomer') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    <!-- Step 5: Confirmation -->
                    @if($currentStep === 5)
                        <div class="space-y-4">
                            <h3 class="text-lg font-medium text-gray-900">Notas</h3>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Notas del Cliente (opcional)</label>
                                <textarea wire:model="notasCliente" rows="3"
                                          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                          placeholder="Preferencias, alergias, etc."></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Notas Internas (opcional)</label>
                                <textarea wire:model="notasInternas" rows="3"
                                          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                          placeholder="Notas privadas del negocio..."></textarea>
                            </div>
                        </div>
                    @endif

                    <!-- Navigation Buttons -->
                    <div class="mt-8 flex justify-between">
                        <button type="button" wire:click="previousStep" 
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 {{ $currentStep === 1 ? 'invisible' : '' }}">
                            Anterior
                        </button>

                        @if($currentStep < 5)
                            <button type="button" wire:click="nextStep" 
                                    class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                                Siguiente
                            </button>
                        @else
                            <button type="submit" 
                                    class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                                Crear Cita
                            </button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
