<div class="space-y-8">

    {{-- Progress Bar --}}
    <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-6">
        <div class="flex items-center justify-between">
            @foreach([1 => 'Servicio', 2 => 'Sucursal', 3 => 'Empleado', 4 => 'Fecha y Hora', 5 => 'Datos', 6 => 'Confirmado'] as $num => $label)
                <div class="flex flex-col items-center">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full text-sm font-bold
                                {{ $step >= $num ? 'bg-emerald-600 text-white' : 'bg-slate-800 text-slate-500' }}">
                        {{ $num }}
                    </div>
                    <span class="mt-2 hidden text-xs font-medium md:block {{ $step >= $num ? 'text-emerald-400' : 'text-slate-500' }}">
                        {{ $label }}
                    </span>
                </div>
                @if(!$loop->last)
                    <div class="flex-1 border-t-2 border-dashed {{ $step > $num ? 'border-emerald-600' : 'border-slate-800' }}"></div>
                @endif
            @endforeach
        </div>
    </div>

    {{-- Step 1: Select Service --}}
    @if($step === 1)
        <div class="space-y-6">
            <div class="text-center">
                <h2 class="text-2xl font-bold text-white">Selecciona un Servicio</h2>
                <p class="mt-2 text-slate-400">Elige el servicio que deseas reservar</p>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach($services as $service)
                    <button wire:click="selectService({{ $service->id }})"
                            class="group rounded-xl border border-slate-800 bg-slate-900/60 p-6 text-left transition hover:border-emerald-500/50 hover:bg-slate-900/80">
                        <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-lg bg-emerald-500/15 text-emerald-300">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                            </svg>
                        </div>
                        <h3 class="text-base font-bold text-white group-hover:text-emerald-300">{{ $service->nombre }}</h3>
                        <p class="mt-1 text-sm text-slate-400">{{ $service->duracion_minutos }} minutos</p>
                        <p class="mt-3 text-lg font-bold text-emerald-400">${{ number_format($service->precio, 2) }}</p>
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Step 2: Select Location --}}
    @if($step === 2)
        <div class="space-y-6">
            <div class="text-center">
                <h2 class="text-2xl font-bold text-white">Selecciona una Sucursal</h2>
                <p class="mt-2 text-slate-400">Elige la sucursal donde deseas tu cita</p>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                @foreach($locations as $location)
                    <button wire:click="selectLocation({{ $location->id }})"
                            class="group rounded-xl border border-slate-800 bg-slate-900/60 p-6 text-left transition hover:border-emerald-500/50 hover:bg-slate-900/80">
                        <h3 class="text-base font-bold text-white group-hover:text-emerald-300">{{ $location->nombre }}</h3>
                        <p class="mt-2 text-sm text-slate-400">{{ $location->direccion }}, {{ $location->ciudad }}</p>
                    </button>
                @endforeach
            </div>

            <div class="flex justify-center">
                <button wire:click="$set('step', 1)" class="text-sm font-medium text-slate-400 transition hover:text-white">
                    ← Volver a servicios
                </button>
            </div>
        </div>
    @endif

    {{-- Step 3: Select Employee --}}
    @if($step === 3)
        <div class="space-y-6">
            <div class="text-center">
                <h2 class="text-2xl font-bold text-white">Selecciona un Empleado</h2>
                <p class="mt-2 text-slate-400">Elige quién te atenderá</p>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach($employees as $employee)
                    <button wire:click="selectEmployee({{ $employee->id }})"
                            class="group rounded-xl border border-slate-800 bg-slate-900/60 p-6 text-center transition hover:border-emerald-500/50 hover:bg-slate-900/80">
                        <div class="mx-auto mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-violet-500/20 text-xl font-bold text-violet-300 ring-1 ring-violet-500/20">
                            {{ strtoupper(substr($employee->nombre, 0, 2)) }}
                        </div>
                        <h3 class="text-base font-bold text-white group-hover:text-emerald-300">{{ $employee->nombre }}</h3>
                    </button>
                @endforeach
            </div>

            <div class="flex justify-center">
                <button wire:click="$set('step', 2)" class="text-sm font-medium text-slate-400 transition hover:text-white">
                    ← Volver a sucursal
                </button>
            </div>
        </div>
    @endif

    {{-- Step 4: Select Date and Time --}}
    @if($step === 4)
        <div class="space-y-6">
            <div class="text-center">
                <h2 class="text-2xl font-bold text-white">Selecciona Fecha y Hora</h2>
                <p class="mt-2 text-slate-400">Elige cuándo deseas tu cita</p>
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-6">
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Fecha</label>
                <input type="date" wire:model.live="selectedDate"
                       min="{{ date('Y-m-d') }}"
                       class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 [color-scheme:dark]">
            </div>

            @if($selectedDate && count($availableTimes) > 0)
                <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-6">
                    <h3 class="mb-4 text-sm font-semibold uppercase tracking-widest text-slate-400">Horarios disponibles</h3>
                    <div class="grid grid-cols-3 gap-3 sm:grid-cols-4 md:grid-cols-6">
                        @foreach($availableTimes as $time)
                            <button wire:click="selectTime('{{ $time }}')"
                                    class="rounded-lg border border-slate-700 bg-slate-800/40 px-3 py-2 text-sm font-medium text-white transition hover:border-emerald-500 hover:bg-emerald-500/10 hover:text-emerald-300">
                                {{ $time }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @elseif($selectedDate && count($availableTimes) === 0)
                <div class="rounded-xl border border-amber-500/20 bg-amber-500/10 p-6 text-center">
                    <p class="text-sm font-semibold text-amber-300">No hay horarios disponibles para esta fecha</p>
                    <p class="mt-1 text-xs text-amber-200/70">Intenta seleccionar otra fecha</p>
                </div>
            @endif

            <div class="flex justify-center">
                <button wire:click="$set('step', 3)" class="text-sm font-medium text-slate-400 transition hover:text-white">
                    ← Volver a empleado
                </button>
            </div>
        </div>
    @endif

    {{-- Step 5: Customer Details --}}
    @if($step === 5)
        <div class="space-y-6">
            <div class="text-center">
                <h2 class="text-2xl font-bold text-white">Tus Datos</h2>
                <p class="mt-2 text-slate-400">Ingresa tu información para confirmar la cita</p>
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-6">
                <form wire:submit="bookAppointment" class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Nombre completo <span class="text-rose-400">*</span></label>
                            <input type="text" wire:model="customerName" class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            @error('customerName') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Email <span class="text-rose-400">*</span></label>
                            <input type="email" wire:model="customerEmail" class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            @error('customerEmail') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Teléfono <span class="text-rose-400">*</span></label>
                            <input type="tel" wire:model="customerPhone" class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            @error('customerPhone') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1.5">Notas (opcional)</label>
                            <textarea wire:model="customerNotes" rows="3" class="w-full rounded-lg border border-slate-700 bg-slate-900/40 px-3 py-2.5 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"></textarea>
                            @error('customerNotes') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex items-center justify-between border-t border-slate-800 pt-4">
                        <button type="button" wire:click="$set('step', 4)" class="text-sm font-medium text-slate-400 transition hover:text-white">
                            ← Volver
                        </button>
                        <button type="submit" class="rounded-lg bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                            Confirmar Cita
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Step 6: Confirmation --}}
    @if($step === 6)
        <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 p-8 text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-500/20">
                <svg class="h-8 w-8 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-emerald-300">¡Cita Reservada Exitosamente!</h2>
            <p class="mt-2 text-slate-300">Tu cita ha sido agendada. Recibirás un correo de confirmación en breve.</p>
            <button wire:click="resetBooking"
                    class="mt-6 rounded-lg bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                Reservar otra cita
            </button>
        </div>
    @endif
</div>
