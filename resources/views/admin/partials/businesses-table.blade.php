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

    $statusIcon = [
        'pending' => '⏳',
        'approved' => '✓',
        'suspended' => '⛔',
        'inactive' => '—',
    ];
@endphp

@if ($businessesTable->count() > 0)
    {{-- Vista tipo cards para desktop --}}
    <div class="hidden lg:block">
        <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900/60">
            <table class="min-w-full divide-y divide-slate-800 text-sm">
                <thead class="bg-slate-950/50 text-xs uppercase tracking-[0.14em] text-slate-400">
                    <tr>
                        <th scope="col" class="px-5 py-3.5 text-left">Negocio</th>
                        <th scope="col" class="px-4 py-3.5 text-left">Plan</th>
                        <th scope="col" class="px-4 py-3.5 text-left">Estado</th>
                        <th scope="col" class="px-3 py-3.5 text-center">Suc.</th>
                        <th scope="col" class="px-3 py-3.5 text-center">Emp.</th>
                        <th scope="col" class="px-3 py-3.5 text-center">Citas/mes</th>
                        <th scope="col" class="px-4 py-3.5 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/70 text-slate-200">
                    @foreach ($businessesTable as $business)
                        @php
                            $plan = strtolower($business->plan ?? 'basic');
                            $estado = strtolower($business->estado ?? 'inactive');
                        @endphp

                        <tr class="group transition hover:bg-slate-800/40">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-800 text-sm font-bold text-slate-300 ring-1 ring-slate-700">
                                        {{ strtoupper(mb_substr($business->nombre, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-white">{{ $business->nombre }}</p>
                                        <p class="mt-0.5 text-xs text-slate-500">Alta: {{ optional($business->created_at)->format('d/m/Y') }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $planBadge[$plan] ?? $planBadge['basic'] }}">
                                    {{ ucfirst($plan) }}
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold {{ $statusBadge[$estado] ?? $statusBadge['inactive'] }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $estado === 'approved' ? 'bg-emerald-400' : ($estado === 'pending' ? 'bg-amber-400' : ($estado === 'suspended' ? 'bg-rose-400' : 'bg-slate-400')) }}"></span>
                                    {{ ucfirst($estado) }}
                                </span>
                            </td>
                            <td class="px-3 py-4 text-center font-medium text-slate-300">{{ $business->locations_count }}</td>
                            <td class="px-3 py-4 text-center font-medium text-slate-300">{{ $business->employees_count }}</td>
                            <td class="px-3 py-4 text-center">
                                <span class="inline-flex items-center rounded-md bg-indigo-500/10 px-2 py-0.5 text-xs font-bold text-indigo-200">{{ $business->citas_mes_count }}</span>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex items-center justify-end gap-1.5">
                                    {{-- Ver detalle (siempre visible) --}}
                                    <button
                                        type="button"
                                        class="js-business-action inline-flex items-center gap-1 rounded-lg border border-slate-700 px-2.5 py-1.5 text-xs font-medium text-slate-300 transition hover:bg-slate-800 hover:text-white"
                                        data-action="view"
                                        data-business-id="{{ $business->id }}"
                                        data-business-name="{{ e($business->nombre) }}"
                                        aria-label="Ver detalle de {{ $business->nombre }}"
                                    >
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" /><path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 0 1 0-1.186A10.004 10.004 0 0 1 10 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0 1 10 17c-4.257 0-7.893-2.66-9.336-6.41ZM14 10a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z" clip-rule="evenodd" /></svg>
                                        Ver
                                    </button>

                                    @can('platform-admin')
                                        {{-- Aprobar (solo si esta pending) --}}
                                        @if($estado === 'pending')
                                            <button
                                                type="button"
                                                class="js-business-action inline-flex items-center gap-1 rounded-lg border border-emerald-500/40 px-2.5 py-1.5 text-xs font-medium text-emerald-200 transition hover:bg-emerald-500/10"
                                                data-action="approve"
                                                data-business-id="{{ $business->id }}"
                                                data-business-name="{{ e($business->nombre) }}"
                                                aria-label="Aprobar negocio {{ $business->nombre }}"
                                            >
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" /></svg>
                                                Aprobar
                                            </button>
                                        @endif

                                        {{-- Suspender (solo si esta approved) --}}
                                        @if($estado === 'approved')
                                            <button
                                                type="button"
                                                class="js-business-action inline-flex items-center gap-1 rounded-lg border border-rose-500/40 px-2.5 py-1.5 text-xs font-medium text-rose-200 transition hover:bg-rose-500/10"
                                                data-action="suspend"
                                                data-business-id="{{ $business->id }}"
                                                data-business-name="{{ e($business->nombre) }}"
                                                aria-label="Suspender negocio {{ $business->nombre }}"
                                            >
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" /></svg>
                                                Suspender
                                            </button>
                                        @endif

                                        {{-- Reactivar (solo si esta suspended) --}}
                                        @if($estado === 'suspended')
                                            <button
                                                type="button"
                                                class="js-business-action inline-flex items-center gap-1 rounded-lg border border-emerald-500/40 px-2.5 py-1.5 text-xs font-medium text-emerald-200 transition hover:bg-emerald-500/10"
                                                data-action="reactivate"
                                                data-business-id="{{ $business->id }}"
                                                data-business-name="{{ e($business->nombre) }}"
                                                aria-label="Reactivar negocio {{ $business->nombre }}"
                                            >
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM8.28 7.22a.75.75 0 0 0-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 1 0 1.06 1.06L10 11.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L11.06 10l1.72-1.72a.75.75 0 0 0-1.06-1.06L10 8.94 8.28 7.22Z" /></svg>
                                                Reactivar
                                            </button>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Vista tipo cards para mobile/tablet --}}
    <div class="lg:hidden space-y-3">
        @foreach ($businessesTable as $business)
            @php
                $plan = strtolower($business->plan ?? 'basic');
                $estado = strtolower($business->estado ?? 'inactive');
            @endphp

            <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4 transition hover:border-slate-700">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-800 text-sm font-bold text-slate-300 ring-1 ring-slate-700">
                            {{ strtoupper(mb_substr($business->nombre, 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-white">{{ $business->nombre }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">{{ optional($business->created_at)->format('d/m/Y') }}</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold shrink-0 {{ $statusBadge[$estado] ?? $statusBadge['inactive'] }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $estado === 'approved' ? 'bg-emerald-400' : ($estado === 'pending' ? 'bg-amber-400' : ($estado === 'suspended' ? 'bg-rose-400' : 'bg-slate-400')) }}"></span>
                        {{ ucfirst($estado) }}
                    </span>
                </div>

                <div class="mt-3 grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-lg bg-slate-800/50 p-2">
                        <p class="text-[10px] uppercase tracking-wide text-slate-500">Plan</p>
                        <p class="mt-0.5 text-xs font-semibold text-slate-200">{{ ucfirst($plan) }}</p>
                    </div>
                    <div class="rounded-lg bg-slate-800/50 p-2">
                        <p class="text-[10px] uppercase tracking-wide text-slate-500">Suc/Emp</p>
                        <p class="mt-0.5 text-xs font-semibold text-slate-200">{{ $business->locations_count }}/{{ $business->employees_count }}</p>
                    </div>
                    <div class="rounded-lg bg-slate-800/50 p-2">
                        <p class="text-[10px] uppercase tracking-wide text-slate-500">Citas</p>
                        <p class="mt-0.5 text-xs font-bold text-indigo-200">{{ $business->citas_mes_count }}</p>
                    </div>
                </div>

                <div class="mt-3 flex items-center gap-2">
                    <button
                        type="button"
                        class="js-business-action flex-1 inline-flex items-center justify-center gap-1 rounded-lg border border-slate-700 px-3 py-2 text-xs font-medium text-slate-300 transition hover:bg-slate-800 hover:text-white"
                        data-action="view"
                        data-business-id="{{ $business->id }}"
                        data-business-name="{{ e($business->nombre) }}"
                    >
                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" /><path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 0 1 0-1.186A10.004 10.004 0 0 1 10 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0 1 10 17c-4.257 0-7.893-2.66-9.336-6.41ZM14 10a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z" clip-rule="evenodd" /></svg>
                        Ver
                    </button>

                    @can('platform-admin')
                        @if($estado === 'pending')
                            <button
                                type="button"
                                class="js-business-action flex-1 inline-flex items-center justify-center gap-1 rounded-lg border border-emerald-500/40 px-3 py-2 text-xs font-medium text-emerald-200 transition hover:bg-emerald-500/10"
                                data-action="approve"
                                data-business-id="{{ $business->id }}"
                                data-business-name="{{ e($business->nombre) }}"
                            >
                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" /></svg>
                                Aprobar
                            </button>
                        @endif

                        @if($estado === 'approved')
                            <button
                                type="button"
                                class="js-business-action flex-1 inline-flex items-center justify-center gap-1 rounded-lg border border-rose-500/40 px-3 py-2 text-xs font-medium text-rose-200 transition hover:bg-rose-500/10"
                                data-action="suspend"
                                data-business-id="{{ $business->id }}"
                                data-business-name="{{ e($business->nombre) }}"
                            >
                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" /></svg>
                                Suspender
                            </button>
                        @endif

                        @if($estado === 'suspended')
                            <button
                                type="button"
                                class="js-business-action flex-1 inline-flex items-center justify-center gap-1 rounded-lg border border-emerald-500/40 px-3 py-2 text-xs font-medium text-emerald-200 transition hover:bg-emerald-500/10"
                                data-action="reactivate"
                                data-business-id="{{ $business->id }}"
                                data-business-name="{{ e($business->nombre) }}"
                            >
                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM8.28 7.22a.75.75 0 0 0-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 1 0 1.06 1.06L10 11.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L11.06 10l1.72-1.72a.75.75 0 0 0-1.06-1.06L10 8.94 8.28 7.22Z" /></svg>
                                Reactivar
                            </button>
                        @endif
                    @endcan
                </div>
            </div>
        @endforeach
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
