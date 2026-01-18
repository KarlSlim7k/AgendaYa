<div class="space-y-6">
    <!-- Period Selector -->
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-900">Panel de Control</h2>
            <div class="flex gap-2">
                <button wire:click="$set('selectedPeriod', 'today')" 
                        class="px-4 py-2 rounded {{ $selectedPeriod === 'today' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700' }}">
                    Hoy
                </button>
                <button wire:click="$set('selectedPeriod', 'week')" 
                        class="px-4 py-2 rounded {{ $selectedPeriod === 'week' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700' }}">
                    Semana
                </button>
                <button wire:click="$set('selectedPeriod', 'month')" 
                        class="px-4 py-2 rounded {{ $selectedPeriod === 'month' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700' }}">
                    Mes
                </button>
                <button wire:click="$set('selectedPeriod', 'year')" 
                        class="px-4 py-2 rounded {{ $selectedPeriod === 'year' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700' }}">
                    Año
                </button>
            </div>
        </div>
    </div>

    <!-- KPIs Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Appointments -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Citas</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $totalAppointments }}</p>
                </div>
                <div class="bg-blue-100 rounded-full p-3">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Confirmed -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Confirmadas</p>
                    <p class="text-3xl font-bold text-blue-600">{{ $confirmedAppointments }}</p>
                </div>
                <div class="bg-blue-100 rounded-full p-3">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Completed -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Completadas</p>
                    <p class="text-3xl font-bold text-green-600">{{ $completedAppointments }}</p>
                </div>
                <div class="bg-green-100 rounded-full p-3">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Revenue -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Ingresos</p>
                    <p class="text-3xl font-bold text-green-600">${{ number_format($revenue, 2) }}</p>
                </div>
                <div class="bg-green-100 rounded-full p-3">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Services -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Servicios Más Solicitados</h3>
            @if(count($topServices) > 0)
                <div class="space-y-3">
                    @foreach($topServices as $service)
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-700">{{ $service['nombre'] }}</span>
                                <span class="font-semibold text-gray-900">{{ $service['total'] }} citas</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ ($service['total'] / max(array_column($topServices, 'total'))) * 100 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 text-center py-8">No hay datos disponibles</p>
            @endif
        </div>

        <!-- Employee Performance -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Rendimiento por Empleado</h3>
            @if(count($employeePerformance) > 0)
                <div class="space-y-3">
                    @foreach($employeePerformance as $emp)
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-700">{{ $emp['nombre'] }}</span>
                                <span class="font-semibold text-gray-900">{{ $emp['total'] }} citas</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-green-600 h-2 rounded-full" style="width: {{ ($emp['total'] / max(array_column($employeePerformance, 'total'))) * 100 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 text-center py-8">No hay datos disponibles</p>
            @endif
        </div>
    </div>

    <!-- Upcoming Appointments -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Próximas Citas</h3>
        </div>
        <div class="divide-y divide-gray-200">
            @forelse($upcomingAppointments as $appointment)
                <div class="p-4 hover:bg-gray-50 transition">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                    <span class="text-blue-600 font-semibold text-lg">
                                        {{ substr($appointment['user']['nombre'], 0, 1) }}{{ substr($appointment['user']['apellidos'] ?? '', 0, 1) }}
                                    </span>
                                </div>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">
                                    {{ $appointment['user']['nombre'] }} {{ $appointment['user']['apellidos'] }}
                                </p>
                                <p class="text-sm text-gray-500">{{ $appointment['service']['nombre'] }}</p>
                                <p class="text-xs text-gray-400">con {{ $appointment['employee']['nombre'] }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-900">
                                {{ \Carbon\Carbon::parse($appointment['fecha_hora_inicio'])->format('d/m/Y') }}
                            </p>
                            <p class="text-sm text-gray-500">
                                {{ \Carbon\Carbon::parse($appointment['fecha_hora_inicio'])->format('H:i') }}
                            </p>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-gray-500">
                    No hay citas próximas
                </div>
            @endforelse
        </div>
    </div>
</div>
