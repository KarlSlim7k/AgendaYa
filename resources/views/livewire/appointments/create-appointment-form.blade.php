<div class="space-y-6">

    @if (session()->has('error'))
        <div class="rounded-xl border border-rose-500/20 bg-rose-500/10 px-4 py-3 text-sm text-rose-300">
            {{ session('error') }}
        </div>
    @endif

    {{-- Progress Steps --}}
    <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-6">
        <div class="flex items-center gap-2">
            @foreach(['Servicio', 'Empleado', 'Fecha y Hora', 'Cliente', 'Notas'] as $index => $step)
                <div class="flex items-center {{ $index < 4 ? 'flex-1' : '' }}">
                    <div class="flex shrink-0 items-center gap-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold
                            {{ $currentStep > $index + 1 ? 'bg-emerald-600 text-white' : ($currentStep === $index + 1 ? 'bg-emerald-500/20 text-emerald-300 ring-1 ring-emerald-500/50' : 'bg-slate-800 text-slate-500') }}">
                            @if($currentStep > $index + 1)
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                            @else
                                {{ $index + 1 }}
                            @endif
                        </div>
                        <span class="hidden text-xs font-medium sm:block {{ $currentStep === $index + 1 ? 'text-emerald-300' : 'text-slate-500' }}">
                            {{ $step }}
                        </span>
                    </div>
                    @if($index < 4)
                        <div class="mx-2 flex-1 h-px {{ $currentStep > $index + 1 ? 'bg-emerald-600/40' : 'bg-slate-800' }}"></div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Form Card --}}
    <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-6">
        <form wire:submit="createAppointment">

            {{-- Step 1: Service --}}
            @if($currentStep === 1)
                <div class="space-y-4">
                    <h3 class="text-sm font-semibold uppercase tracking-widest text-slate-400">Selecciona un Servicio</h3>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        @foreach($services as $service)
                            <div wire:click="$set('selectedService', {{ $service->id }})"
                                 class="cursor-pointer rounded-xl border-2 p-4 transition
                                        {{ $selectedService === $service->id
                                            ? 'border-emerald-500/60 bg-emerald-500/10'
                                            : 'border-slate-800 bg-slate-800/30 hover:border-slate-700' }}">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-white">{{ $service->nombre }}</h4>
                                        @if($service->descripcion)
                                            <p class="mt-1 text-xs text-slate-400">{{ $service->descripcion }}</p>
                                        @endif
                                        <div class="mt-2 flex items-center gap-4">
                                            <span class="text-xs text-slate-500">{{ $service->duracion_minutos }} min</span>
                                            <span class="text-sm font-bold text-emerald-400">${{ number_format($service->precio, 2) }}</span>
                                        </div>
                                    </div>
                                    @if($selectedService === $service->id)
                                        <div class="ml-3 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-600">
                                            <svg class="h-3.5 w-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @error('selectedService') <p class="text-xs text-rose-400">{{ $message }}</p> @enderror
                </div>
            @endif

            {{-- Step 2: Employee --}}
            @if($currentStep === 2)
                <div class="space-y-4">
                    <h3 class="text-sm font-semibold uppercase tracking-widest text-slate-400">Selecciona un Empleado</h3>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        @foreach($employees as $employee)
                            <div wire:click="$set('selectedEmployee', {{ $employee->id }})"
                                 class="cursor-pointer rounded-xl border-2 p-4 transition
                                        {{ $selectedEmployee === $employee->id
                                            ? 'border-emerald-500/60 bg-emerald-500/10'
                                            : 'border-slate-800 bg-slate-800/30 hover:border-slate-700' }}">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-500/20 text-sm font-bold text-blue-300">
                                        {{ strtoupper(substr($employee->nombre, 0, 1)) }}
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-white">{{ $employee->nombre }}</h4>
                                        @if($employee->cargo)
                                            <p class="text-xs text-slate-400">{{ $employee->cargo }}</p>
                                        @endif
                                    </div>
                                    @if($selectedEmployee === $employee->id)
                                        <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-600">
                                            <svg class="h-3.5 w-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @error('selectedEmployee') <p class="text-xs text-rose-400">{{ $message }}</p> @enderror
                </div>
            @endif

            {{-- Step 3: Date & Time --}}
            @if($currentStep === 3)
                <div class="space-y-5">
                    <h3 class="text-sm font-semibold uppercase tracking-widest text-slate-400">Selecciona Fecha y Hora</h3>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Fecha</label>
                        <input type="date" wire:model.live="selectedDate"
                               class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 [color-scheme:dark]">
                        @error('selectedDate') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                    </div>

                    @if(count($availableSlots) > 0)
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Horarios Disponibles</label>
                            <div class="grid grid-cols-4 gap-2 md:grid-cols-6">
                                @foreach($availableSlots as $slot)
                                    <button type="button"
                                            wire:click="$set('selectedSlot', '{{ $slot['fecha_hora_inicio'] }}')"
                                            class="rounded-lg px-3 py-2 text-sm font-medium transition
                                                   {{ $selectedSlot === $slot['fecha_hora_inicio']
                                                       ? 'bg-emerald-600 text-white'
                                                       : 'border border-slate-700 text-slate-400 hover:border-emerald-500/40 hover:text-emerald-300' }}">
                                        {{ \Carbon\Carbon::parse($slot['fecha_hora_inicio'])->format('H:i') }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @elseif($selectedDate)
                        <p class="text-sm text-slate-500">No hay horarios disponibles para esta fecha.</p>
                    @endif
                    @error('selectedSlot') <p class="text-xs text-rose-400">{{ $message }}</p> @enderror
                </div>
            @endif

            {{-- Step 4: Customer --}}
            @if($currentStep === 4)
                <div class="space-y-4">
                    <h3 class="text-sm font-semibold uppercase tracking-widest text-slate-400">Selecciona Cliente</h3>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Buscar Cliente</label>
                        <input type="text" wire:model.live="customerSearch"
                               placeholder="Nombre o email del cliente..."
                               class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">

                        @if(count($searchResults) > 0)
                            <div class="mt-2 overflow-hidden rounded-xl border border-slate-700">
                                @foreach($searchResults as $customer)
                                    <div wire:click="selectCustomer({{ $customer['id'] }})"
                                         class="cursor-pointer border-b border-slate-800/60 px-4 py-3 transition last:border-0 hover:bg-slate-800/50">
                                        <p class="text-sm font-semibold text-white">{{ $customer['nombre'] }}</p>
                                        <p class="text-xs text-slate-400">{{ $customer['email'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    @error('selectedCustomer') <p class="text-xs text-rose-400">{{ $message }}</p> @enderror
                </div>
            @endif

            {{-- Step 5: Notes --}}
            @if($currentStep === 5)
                <div class="space-y-5">
                    <h3 class="text-sm font-semibold uppercase tracking-widest text-slate-400">Notas (Opcional)</h3>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Notas del Cliente</label>
                        <textarea wire:model="notasCliente" rows="3"
                                  placeholder="Preferencias, alergias, etc."
                                  class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Notas Internas</label>
                        <textarea wire:model="notasInternas" rows="3"
                                  placeholder="Notas privadas del negocio..."
                                  class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"></textarea>
                    </div>
                </div>
            @endif

            {{-- Navigation --}}
            <div class="mt-6 flex justify-between">
                <button type="button" wire:click="previousStep"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-700 px-4 py-2 text-sm font-medium text-slate-400 transition hover:bg-slate-800 hover:text-white {{ $currentStep === 1 ? 'invisible' : '' }}">
                    ← Anterior
                </button>

                @if($currentStep < 5)
                    <button type="button" wire:click="nextStep"
                            class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                        Siguiente →
                    </button>
                @else
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                        Crear Cita
                    </button>
                @endif
            </div>
        </form>
    </div>
</div>
