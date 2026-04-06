<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">Perfil del Negocio</h2>
                    <p class="mt-1 text-sm text-gray-600">Administra la información de tu negocio.</p>
                </div>

                @if (session()->has('message'))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
                         class="mb-4 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg">
                        {{ session('message') }}
                    </div>
                @endif

                @if (session()->has('error'))
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                        {{ session('error') }}
                    </div>
                @endif

                @if (!$business)
                    <p class="text-gray-500">Cargando información del negocio...</p>
                @else

                <form wire:submit="save">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Nombre del Negocio -->
                        <div class="md:col-span-2">
                            <label for="nombre" class="block text-sm font-medium text-gray-700">
                                Nombre del Negocio <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="nombre" wire:model="nombre"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('nombre')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Razón Social -->
                        <div>
                            <label for="razon_social" class="block text-sm font-medium text-gray-700">
                                Razón Social
                            </label>
                            <input type="text" id="razon_social" wire:model="razon_social"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('razon_social')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- RFC -->
                        <div>
                            <label for="rfc" class="block text-sm font-medium text-gray-700">
                                RFC
                            </label>
                            <input type="text" id="rfc" wire:model="rfc" placeholder="ABC123456XYZ"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm uppercase">
                            @error('rfc')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">
                                Email <span class="text-red-500">*</span>
                            </label>
                            <input type="email" id="email" wire:model="email"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Teléfono -->
                        <div>
                            <label for="telefono" class="block text-sm font-medium text-gray-700">
                                Teléfono <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="telefono" wire:model="telefono" placeholder="+52 55 1234 5678"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('telefono')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Categoría -->
                        <div class="md:col-span-2">
                            <label for="categoria" class="block text-sm font-medium text-gray-700">
                                Categoría <span class="text-red-500">*</span>
                            </label>
                            <select id="categoria" wire:model="categoria"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Selecciona una categoría</option>
                                <option value="peluqueria">Peluquería</option>
                                <option value="clinica">Clínica</option>
                                <option value="taller">Taller</option>
                                <option value="spa">Spa</option>
                                <option value="consultorio">Consultorio</option>
                                <option value="gimnasio">Gimnasio</option>
                                <option value="restaurante">Restaurante</option>
                                <option value="otro">Otro</option>
                            </select>
                            @error('categoria')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Descripción -->
                        <div class="md:col-span-2">
                            <label for="descripcion" class="block text-sm font-medium text-gray-700">
                                Descripción
                            </label>
                            <textarea id="descripcion" wire:model="descripcion" rows="4"
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                      placeholder="Describe tu negocio..."></textarea>
                            @error('descripcion')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500">Máximo 1000 caracteres</p>
                        </div>
                    </div>

                    <!-- Botones de acción -->
                    <div class="mt-6 flex items-center justify-end space-x-3">
                        <button type="button" onclick="window.location.href='{{ route('business.dashboard') }}'"
                                class="px-4 py-2 bg-white border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Guardar Cambios
                        </button>
                    </div>
                </form>

                <!-- Información adicional -->
                <div class="mt-8 pt-8 border-t border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Sucursales</h3>
                    <div class="space-y-2">
                        @forelse ($business->locations as $location)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div>
                                    <p class="font-medium text-gray-900">{{ $location->nombre }}</p>
                                    <p class="text-sm text-gray-600">{{ $location->direccion }}, {{ $location->ciudad }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">No hay sucursales registradas.</p>
                        @endforelse
                    </div>
                </div>

                @endif
            </div>
        </div>
    </div>
</div>
