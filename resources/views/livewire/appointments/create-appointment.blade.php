<div class="space-y-6">

    @if($showSuccess)
        {{-- Success Screen --}}
        <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-8 text-center">
            <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-500/20 ring-1 ring-emerald-500/30">
                <svg class="h-8 w-8 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-white mb-1">¡Cita creada exitosamente!</h3>
            <p class="text-sm text-slate-400 mb-6">El cliente recibirá una confirmación por email</p>

            <div class="mx-auto max-w-sm space-y-2 text-left">
                @foreach([
                    ['Código', $createdAppointment->codigo_confirmacion],
                    ['Cliente', $createdAppointment->user->nombre . ' ' . $createdAppointment->user->apellidos],
                    ['Email', $createdAppointment->user->email],
                    ['Servicio', $createdAppointment->service->nombre],
                    ['Empleado', $createdAppointment->employee->nombre],
                    ['Fecha y hora', $createdAppointment->fecha_hora_inicio->format('d/m/Y H:i')],
                    ['Duración', $createdAppointment->service->duracion_minutos . ' minutos'],
                ] as [$label, $value])
                    <div class="flex items-center justify-between rounded-lg bg-slate-800/40 px-4 py-2.5">
                        <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</span>
                        <span class="text-sm font-medium text-white font-mono">{{ $value }}</span>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 flex gap-3 justify-center">
                <a href="{{ route('business.appointments.index') }}"
                   class="inline-flex items-center gap-2 rounded-lg border border-slate-700 px-4 py-2 text-sm font-medium text-slate-400 transition hover:bg-slate-800 hover:text-white">
                    Ver todas las citas
                </a>
                <button wire:click="createAnother"
                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                    Crear otra cita
                </button>
            </div>
        </div>

    @else
        {{-- Progress Steps --}}
        <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-6">
            <div class="flex items-center">
                @foreach(['Seleccionar Servicio', 'Fecha y Hora', 'Datos del Cliente'] as $index => $step)
                    <div class="flex items-center {{ $index < 2 ? 'flex-1' : '' }}">
                        <div class="flex shrink-0 items-center gap-2">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold
                                {{ $currentStep > $index + 1 ? 'bg-emerald-600 text-white' : ($currentStep >= $index + 1 ? 'bg-emerald-500/20 text-emerald-300 ring-1 ring-emerald-500/50' : 'bg-slate-800 text-slate-500') }}">
                                @if($currentStep > $index + 1)
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                @else
                                    {{ $index + 1 }}
                                @endif
                            </div>
                            <span class="hidden text-xs font-medium sm:block {{ $currentStep >= $index + 1 ? 'text-emerald-300' : 'text-slate-500' }}">
                                {{ $step }}
                            </span>
                        </div>
                        @if($index < 2)
                            <div class="mx-2 flex-1 h-px {{ $currentStep > $index + 1 ? 'bg-emerald-600/40' : 'bg-slate-800' }}"></div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Form --}}
        <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-6">
            <form wire:submit.prevent="submit">

                {{-- Step 1: Service Selection --}}
                @if($currentStep === 1)
                    <div class="space-y-4">
                        <h3 class="text-sm font-semibold uppercase tracking-widest text-slate-400">Seleccione el servicio <span class="text-rose-400">*</span></h3>
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            @forelse($services as $service)
                                <div wire:click="$set('serviceId', {{ $service->id }})"
                                     class="cursor-pointer rounded-xl border-2 p-4 transition
                                            {{ $serviceId == $service->id
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
                                        @if($serviceId == $service->id)
                                            <div class="ml-3 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-600">
                                                <svg class="h-3.5 w-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="col-span-2 py-10 text-center text-sm text-slate-500">
                                    No hay servicios disponibles
                                </div>
                            @endforelse
                        </div>
                        @error('serviceId') <p class="text-xs text-rose-400">{{ $message }}</p> @enderror

                        <div class="flex justify-end">
                            <button type="button" wire:click="nextStep"
                                    class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:opacity-50"
                                    {{ !$serviceId ? 'disabled' : '' }}>
                                Siguiente →
                            </button>
                        </div>
                    </div>
                @endif

                {{-- Step 2: Employee, Date & Time --}}
                @if($currentStep === 2)
                    <div class="space-y-5">

                        @if($selectedService)
                            <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3">
                                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-400">Servicio seleccionado</p>
                                <p class="mt-0.5 text-sm font-semibold text-white">{{ $selectedService->nombre }}</p>
                                <p class="text-xs text-slate-400">{{ $selectedService->duracion_minutos }} min • ${{ number_format($selectedService->precio, 2) }}</p>
                            </div>
                        @endif

                        <div>
                            <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-3">Seleccione el empleado <span class="text-rose-400">*</span></h3>
                            @if($loadingEmployees)
                                <div class="flex items-center justify-center py-8">
                                    <div class="h-8 w-8 animate-spin rounded-full border-2 border-slate-700 border-t-emerald-500"></div>
                                </div>
                            @else
                                <div class="grid grid-cols-2 gap-3 md:grid-cols-3">
                                    @forelse($employees as $employee)
                                        <div wire:click="$set('employeeId', {{ $employee->id }})"
                                             class="cursor-pointer rounded-xl border-2 p-4 text-center transition
                                                    {{ $employeeId == $employee->id
                                                        ? 'border-emerald-500/60 bg-emerald-500/10'
                                                        : 'border-slate-800 bg-slate-800/30 hover:border-slate-700' }}">
                                            <div class="mx-auto mb-2 flex h-12 w-12 items-center justify-center rounded-full bg-blue-500/20 text-lg font-bold text-blue-300">
                                                {{ strtoupper(substr($employee->nombre, 0, 1)) }}
                                            </div>
                                            <p class="text-sm font-semibold text-white">{{ $employee->nombre }}</p>
                                            @if($employeeId == $employee->id)
                                                <div class="mx-auto mt-2 flex h-5 w-5 items-center justify-center rounded-full bg-emerald-600">
                                                    <svg class="h-3 w-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                </div>
                                            @endif
                                        </div>
                                    @empty
                                        <div class="col-span-3 py-6 text-center text-sm text-slate-500">
                                            No hay empleados disponibles para este servicio
                                        </div>
                                    @endforelse
                                </div>
                            @endif
                            @error('employeeId') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Seleccione la fecha <span class="text-rose-400">*</span></label>
                            <input type="date"
                                   wire:model.live="selectedDate"
                                   min="{{ \Carbon\Carbon::today()->format('Y-m-d') }}"
                                   max="{{ \Carbon\Carbon::today()->addMonths(2)->format('Y-m-d') }}"
                                   class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 [color-scheme:dark]">
                            @error('selectedDate') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                        </div>

                        @if($employeeId && $selectedDate)
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Seleccione el horario <span class="text-rose-400">*</span></label>
                                @if($loadingSlots)
                                    <div class="flex items-center gap-2 py-4">
                                        <div class="h-5 w-5 animate-spin rounded-full border-2 border-slate-700 border-t-emerald-500"></div>
                                        <span class="text-xs text-slate-500">Cargando disponibilidad...</span>
                                    </div>
                                @else
                                    <div class="grid grid-cols-4 gap-2 sm:grid-cols-6 md:grid-cols-8">
                                        @forelse($availableSlots as $slot)
                                            <button type="button"
                                                    wire:click="$set('selectedSlot', '{{ $slot['time'] }}')"
                                                    class="rounded-lg px-3 py-2 text-sm font-medium transition
                                                           {{ $selectedSlot == $slot['time']
                                                               ? 'bg-emerald-600 text-white'
                                                               : 'border border-slate-700 text-slate-400 hover:border-emerald-500/40 hover:text-emerald-300' }}">
                                                {{ $slot['display'] }}
                                            </button>
                                        @empty
                                            <div class="col-span-full py-6 text-center text-sm text-slate-500">
                                                No hay horarios disponibles para esta fecha
                                            </div>
                                        @endforelse
                                    </div>
                                @endif
                                @error('selectedSlot') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                            </div>
                        @endif

                        <div class="flex justify-between pt-2">
                            <button type="button" wire:click="previousStep"
                                    class="inline-flex items-center gap-2 rounded-lg border border-slate-700 px-4 py-2 text-sm font-medium text-slate-400 transition hover:bg-slate-800 hover:text-white">
                                ← Anterior
                            </button>
                            <button type="button" wire:click="nextStep"
                                    class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:opacity-50"
                                    {{ !$employeeId || !$selectedDate || !$selectedSlot ? 'disabled' : '' }}>
                                Siguiente →
                            </button>
                        </div>
                    </div>
                @endif

                {{-- Step 3: Customer Info --}}
                @if($currentStep === 3)
                    <div class="space-y-5">

                        {{-- Summary --}}
                        <div class="rounded-xl border border-slate-700/60 bg-slate-800/30 p-4">
                            <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Resumen de la cita</h3>
                            <div class="grid grid-cols-2 gap-2 text-sm">
                                <span class="text-slate-500">Servicio:</span>
                                <span class="font-medium text-white">{{ $selectedService->nombre }}</span>
                                <span class="text-slate-500">Empleado:</span>
                                <span class="font-medium text-white">{{ $selectedEmployee->nombre }}</span>
                                <span class="text-slate-500">Fecha:</span>
                                <span class="font-medium text-white">{{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}</span>
                                <span class="text-slate-500">Hora:</span>
                                <span class="font-medium text-white">{{ \Carbon\Carbon::parse($selectedSlot)->format('H:i') }}</span>
                                <span class="text-slate-500">Duración:</span>
                                <span class="font-medium text-white">{{ $selectedService->duracion_minutos }} minutos</span>
                                <span class="text-slate-500">Precio:</span>
                                <span class="font-bold text-emerald-400">${{ number_format($selectedService->precio, 2) }}</span>
                            </div>
                        </div>

                        {{-- Customer Fields --}}
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Nombre completo <span class="text-rose-400">*</span></label>
                                <input type="text" wire:model="userName"
                                       placeholder="Ej: Juan Pérez García"
                                       class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                                @error('userName') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Email <span class="text-rose-400">*</span></label>
                                <input type="email" wire:model="userEmail"
                                       placeholder="ejemplo@email.com"
                                       class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                                @error('userEmail') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Teléfono</label>
                                <input type="tel" wire:model="userPhone"
                                       placeholder="+52 55 1234 5678"
                                       class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                                @error('userPhone') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Notas adicionales</label>
                                <textarea wire:model="notes" rows="3"
                                          placeholder="Información adicional sobre la cita..."
                                          class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"></textarea>
                                @error('notes') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        @error('submit')
                            <div class="rounded-xl border border-rose-500/20 bg-rose-500/10 px-4 py-3 text-sm text-rose-300">
                                {{ $message }}
                            </div>
                        @enderror

                        <div class="flex justify-between pt-2">
                            <button type="button" wire:click="previousStep"
                                    class="inline-flex items-center gap-2 rounded-lg border border-slate-700 px-4 py-2 text-sm font-medium text-slate-400 transition hover:bg-slate-800 hover:text-white">
                                ← Anterior
                            </button>
                            <button type="submit"
                                    wire:loading.attr="disabled"
                                    class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:opacity-60">
                                <span wire:loading.remove>Crear Cita</span>
                                <span wire:loading class="flex items-center gap-2">
                                    <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                    </svg>
                                    Creando...
                                </span>
                            </button>
                        </div>
                    </div>
                @endif

            </form>
        </div>
    @endif
</div>
