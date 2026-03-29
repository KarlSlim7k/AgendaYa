@php
    $planBadge = [
        'basic' => 'bg-indigo-500/15 text-indigo-200 ring-1 ring-indigo-400/30',
        'standard' => 'bg-violet-500/15 text-violet-200 ring-1 ring-violet-400/30',
        'premium' => 'bg-purple-500/15 text-purple-200 ring-1 ring-purple-400/30',
    ];

    $statusBadge = [
        'pending' => 'bg-amber-500/15 text-amber-200 ring-1 ring-amber-400/30',
        'approved' => 'bg-emerald-500/15 text-emerald-200 ring-1 ring-emerald-400/30',
        'suspended' => 'bg-rose-500/15 text-rose-200 ring-1 ring-rose-400/30',
        'inactive' => 'bg-slate-600/20 text-slate-200 ring-1 ring-slate-500/30',
    ];
@endphp

@if ($businessesTable->count() > 0)
    <div class="overflow-x-auto rounded-xl border border-slate-800 bg-slate-900/60">
        <table class="min-w-full divide-y divide-slate-800 text-sm">
            <thead class="bg-slate-950/50 text-xs uppercase tracking-[0.14em] text-slate-400">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left">Negocio</th>
                    <th scope="col" class="px-4 py-3 text-left">Plan</th>
                    <th scope="col" class="px-4 py-3 text-left">Estado</th>
                    <th scope="col" class="px-4 py-3 text-center">Sucursales</th>
                    <th scope="col" class="px-4 py-3 text-center">Empleados</th>
                    <th scope="col" class="px-4 py-3 text-center">Citas este mes</th>
                    <th scope="col" class="px-4 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/70 text-slate-200">
                @foreach ($businessesTable as $business)
                    @php
                        $plan = strtolower($business->plan ?? 'basic');
                        $estado = strtolower($business->estado ?? 'inactive');
                    @endphp

                    <tr class="transition hover:bg-slate-800/40">
                        <td class="px-4 py-3">
                            <p class="font-semibold text-white">{{ $business->nombre }}</p>
                            <p class="mt-0.5 text-xs text-slate-400">Alta: {{ optional($business->created_at)->format('d/m/Y') }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $planBadge[$plan] ?? $planBadge['basic'] }}">
                                {{ strtoupper($plan) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusBadge[$estado] ?? $statusBadge['inactive'] }}">
                                {{ strtoupper($estado) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center font-medium">{{ $business->locations_count }}</td>
                        <td class="px-4 py-3 text-center font-medium">{{ $business->employees_count }}</td>
                        <td class="px-4 py-3 text-center font-bold text-indigo-200">{{ $business->citas_mes_count }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <a
                                    href="{{ url('/api/v1/businesses/' . $business->id) }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center rounded-lg border border-slate-700 px-2.5 py-1.5 text-xs font-semibold text-slate-200 transition hover:bg-slate-800"
                                    aria-label="Ver negocio {{ $business->nombre }}"
                                >
                                    Ver
                                </a>

                                @can('platform-admin')
                                    <button
                                        type="button"
                                        class="js-business-action inline-flex items-center rounded-lg border border-rose-500/40 px-2.5 py-1.5 text-xs font-semibold text-rose-200 transition hover:bg-rose-500/10"
                                        data-action="suspend"
                                        data-business-id="{{ $business->id }}"
                                        data-business-name="{{ e($business->nombre) }}"
                                        aria-label="Suspender negocio {{ $business->nombre }}"
                                    >
                                        Suspender
                                    </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="businesses-pagination mt-4 text-sm text-slate-300">
        {{ $businessesTable->links() }}
    </div>
@else
    <div class="rounded-xl border border-dashed border-slate-700 bg-slate-900/40 p-8 text-center">
        <svg class="mx-auto h-10 w-10 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5A2.25 2.25 0 0 1 5.25 5.25h13.5A2.25 2.25 0 0 1 21 7.5v9A2.25 2.25 0 0 1 18.75 18.75H5.25A2.25 2.25 0 0 1 3 16.5v-9Zm4.5 3h9m-9 3h6" />
        </svg>
        <p class="mt-3 text-sm font-semibold text-slate-200">No hay negocios para este filtro.</p>
        <p class="mt-1 text-xs text-slate-400">Prueba con otro estado o limpia el filtro.</p>
    </div>
@endif
