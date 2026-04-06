<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', $pageTitle ?? 'Dashboard Admin') - {{ config('app.name', 'Citas Empresariales') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @include('partials.vite-assets')
    @stack('head')
</head>
<body class="bg-slate-900 text-slate-100 antialiased [font-family:Inter,sans-serif]">
@php
    $currentSection = request()->query('seccion', 'dashboard');
    $systemHealthy = $system_is_healthy ?? true;
    $failedJobsTotal = $failed_jobs_total ?? 0;
    $notificationsCount = $topbar_notifications_count ?? 0;
    $appVersion = config('app.version', env('APP_VERSION', '1.0.0'));

    $adminUser = auth()->user();
    $adminName = trim(implode(' ', array_filter([
        $adminUser?->nombre ?? $adminUser?->name,
        $adminUser?->apellidos,
    ])));
    $adminName = $adminName !== '' ? $adminName : 'Administrador de Plataforma';

    $initials = collect(explode(' ', $adminName))
        ->filter()
        ->take(2)
        ->map(fn ($part) => strtoupper(mb_substr($part, 0, 1)))
        ->implode('');

    $activeLinkClass = 'flex items-center gap-3 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-lg shadow-indigo-600/20';
    $baseLinkClass = 'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-300 transition hover:bg-slate-800 hover:text-white';

    $dashboardRoute = route('admin.dashboard');
@endphp

<div x-data="{ open: false }" class="min-h-screen">
    <aside
        :class="open ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
        class="fixed inset-y-0 left-0 z-50 w-60 border-r border-slate-800 bg-slate-950/95 backdrop-blur transition-transform duration-300"
        aria-label="Sidebar de administracion"
    >
        <div class="flex h-full flex-col">
            <div class="border-b border-slate-800 px-5 py-6">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-300">Panel Admin</p>
                <h1 class="mt-2 text-lg font-bold tracking-tight text-white">Citas Empresariales</h1>
            </div>

            <nav class="flex-1 space-y-6 overflow-y-auto px-4 py-5" tabindex="0" aria-label="Navegacion principal">
                <section>
                    <p class="px-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Plataforma</p>
                    <div class="mt-3 space-y-1">
                        <a href="{{ $dashboardRoute }}" class="{{ $currentSection === 'dashboard' ? $activeLinkClass : $baseLinkClass }}" aria-label="Ir a dashboard">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M10.75 2.75a.75.75 0 0 0-1.5 0v5.5h-5.5a.75.75 0 0 0 0 1.5h5.5v5.5a.75.75 0 0 0 1.5 0v-5.5h5.5a.75.75 0 0 0 0-1.5h-5.5v-5.5Z" />
                            </svg>
                            <span>Dashboard</span>
                        </a>

                        <a href="{{ route('admin.dashboard', ['seccion' => 'negocios']) }}#negocios-tenants" class="{{ $currentSection === 'negocios' ? $activeLinkClass : $baseLinkClass }}" aria-label="Ir a negocios y tenants">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M3 4.25A2.25 2.25 0 0 1 5.25 2h9.5A2.25 2.25 0 0 1 17 4.25v11.5A2.25 2.25 0 0 1 14.75 18h-9.5A2.25 2.25 0 0 1 3 15.75V4.25Zm3.5.5a.75.75 0 0 0 0 1.5h7a.75.75 0 0 0 0-1.5h-7Zm0 3.5a.75.75 0 0 0 0 1.5h7a.75.75 0 0 0 0-1.5h-7Zm0 3.5a.75.75 0 0 0 0 1.5h4a.75.75 0 0 0 0-1.5h-4Z" />
                            </svg>
                            <span>Negocios/Tenants</span>
                        </a>

                        <a href="{{ route('admin.dashboard', ['seccion' => 'usuarios']) }}#usuarios-globales" class="{{ $currentSection === 'usuarios' ? $activeLinkClass : $baseLinkClass }}" aria-label="Ir a usuarios globales">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M10 2a4 4 0 1 0 0 8 4 4 0 0 0 0-8ZM4 14a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-2Z" />
                            </svg>
                            <span>Usuarios Globales</span>
                        </a>

                        <a href="{{ route('admin.dashboard', ['seccion' => 'configuracion']) }}#configuracion-rapida" class="{{ $currentSection === 'configuracion' ? $activeLinkClass : $baseLinkClass }}" aria-label="Ir a configuracion de plataforma">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M7.84 1.804a1 1 0 0 1 1.32-.753l.163.058a1.75 1.75 0 0 0 1.354 0l.163-.058a1 1 0 0 1 1.32.753l.034.17a1.75 1.75 0 0 0 .99 1.224l.157.074a1 1 0 0 1 .43 1.404l-.088.15a1.75 1.75 0 0 0 0 1.748l.088.15a1 1 0 0 1-.43 1.404l-.157.074a1.75 1.75 0 0 0-.99 1.224l-.034.17a1 1 0 0 1-1.32.753l-.163-.058a1.75 1.75 0 0 0-1.354 0l-.163.058a1 1 0 0 1-1.32-.753l-.034-.17a1.75 1.75 0 0 0-.99-1.224l-.157-.074a1 1 0 0 1-.43-1.404l.088-.15a1.75 1.75 0 0 0 0-1.748l-.088-.15a1 1 0 0 1 .43-1.404l.157-.074a1.75 1.75 0 0 0 .99-1.224l.034-.17Zm2.16 7.696a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" clip-rule="evenodd" />
                            </svg>
                            <span>Configuracion</span>
                        </a>
                    </div>
                </section>

                <section>
                    <p class="px-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Operaciones</p>
                    <div class="mt-3 space-y-1">
                        <a href="{{ route('admin.dashboard', ['seccion' => 'citas']) }}#monitor-citas" class="{{ $currentSection === 'citas' ? $activeLinkClass : $baseLinkClass }}" aria-label="Ir a monitor de citas">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.75A2.25 2.25 0 0 1 18 6.25v9.5A2.25 2.25 0 0 1 15.75 18h-11.5A2.25 2.25 0 0 1 2 15.75v-9.5A2.25 2.25 0 0 1 4.25 4H5V2.75A.75.75 0 0 1 5.75 2Z" />
                            </svg>
                            <span>Citas (todas)</span>
                        </a>

                        <a href="{{ route('admin.dashboard', ['seccion' => 'notificaciones']) }}#notificaciones-log" class="{{ $currentSection === 'notificaciones' ? $activeLinkClass : $baseLinkClass }}" aria-label="Ir al log de notificaciones">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M10 2.5a4 4 0 0 0-4 4V8c0 .795-.316 1.558-.879 2.121l-.56.56A1.5 1.5 0 0 0 5.621 13h8.758a1.5 1.5 0 0 0 1.06-2.56l-.56-.56A3 3 0 0 1 14 8V6.5a4 4 0 0 0-4-4Zm0 15a2.25 2.25 0 0 0 2.122-1.5H7.878A2.25 2.25 0 0 0 10 17.5Z" />
                            </svg>
                            <span>Notificaciones Log</span>
                        </a>

                        <a href="{{ route('admin.dashboard', ['seccion' => 'jobs']) }}#cola-jobs" class="{{ $currentSection === 'jobs' ? $activeLinkClass : $baseLinkClass }}" aria-label="Ir a cola de jobs">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M3 5.25A2.25 2.25 0 0 1 5.25 3h9.5A2.25 2.25 0 0 1 17 5.25v9.5A2.25 2.25 0 0 1 14.75 17h-9.5A2.25 2.25 0 0 1 3 14.75v-9.5Zm4 .5a.75.75 0 0 0 0 1.5h6a.75.75 0 0 0 0-1.5H7Zm0 3.5a.75.75 0 0 0 0 1.5h6a.75.75 0 0 0 0-1.5H7Zm0 3.5a.75.75 0 0 0 0 1.5h3a.75.75 0 0 0 0-1.5H7Z" clip-rule="evenodd" />
                            </svg>
                            <span>Cola de Jobs</span>
                        </a>
                    </div>
                </section>

                <section>
                    <p class="px-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Sistema</p>
                    <div class="mt-3 space-y-1">
                        <a href="{{ route('admin.dashboard', ['seccion' => 'roles']) }}#roles-permisos" class="{{ $currentSection === 'roles' ? $activeLinkClass : $baseLinkClass }}" aria-label="Ir a roles y permisos">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M9.25 2a2.75 2.75 0 0 0-2.739 2.5H5.75a2.75 2.75 0 1 0 0 5.5h.761A2.75 2.75 0 1 0 11 8.739V8h3.25a2.75 2.75 0 1 0 0-5.5H11v.739A2.75 2.75 0 0 0 9.25 2Z" />
                                <path d="M5 13.25A2.25 2.25 0 0 1 7.25 11h5.5A2.25 2.25 0 0 1 15 13.25v3A1.75 1.75 0 0 1 13.25 18h-6.5A1.75 1.75 0 0 1 5 16.25v-3Z" />
                            </svg>
                            <span>Roles &amp; Permisos</span>
                        </a>

                        <a href="{{ route('admin.dashboard', ['seccion' => 'finanzas']) }}#reportes-financieros" class="{{ $currentSection === 'finanzas' ? $activeLinkClass : $baseLinkClass }}" aria-label="Ir a reportes financieros">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M2 5.75A2.75 2.75 0 0 1 4.75 3h10.5A2.75 2.75 0 0 1 18 5.75v8.5A2.75 2.75 0 0 1 15.25 17H4.75A2.75 2.75 0 0 1 2 14.25v-8.5Zm5.25 1.5a.75.75 0 0 0 0 1.5h5.5a.75.75 0 0 0 0-1.5h-5.5Zm0 3a.75.75 0 0 0 0 1.5h2.5a.75.75 0 0 0 0-1.5h-2.5Z" />
                            </svg>
                            <span>Reportes Financieros</span>
                        </a>

                        <a href="{{ route('admin.dashboard', ['seccion' => 'salud']) }}#salud-sistema" class="{{ $currentSection === 'salud' ? $activeLinkClass : $baseLinkClass }}" aria-label="Ir a salud del sistema">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 2.5a7.5 7.5 0 1 0 0 15 7.5 7.5 0 0 0 0-15ZM8.72 6.22a.75.75 0 0 1 1.06 0l3 3a.75.75 0 0 1-1.06 1.06L10 8.56l-1.72 1.72a.75.75 0 0 1-1.06-1.06l1.72-1.72-1.72-1.72a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                            </svg>
                            <span>Salud del Sistema</span>
                        </a>
                    </div>
                </section>
            </nav>

            <div class="border-t border-slate-800 px-4 py-4">
                @auth
                    <div class="flex items-center gap-3 rounded-lg bg-slate-900 px-3 py-2">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-500/20 text-sm font-bold text-indigo-200" aria-hidden="true">
                            {{ $initials !== '' ? $initials : 'AD' }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-white">{{ $adminName }}</p>
                            <p class="truncate text-xs text-slate-400">PLATAFORMA_ADMIN</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('logout') }}" class="mt-3">
                        @csrf
                        <button
                            type="submit"
                            class="w-full rounded-lg border border-slate-700 px-3 py-2 text-sm font-medium text-slate-200 transition hover:border-slate-500 hover:bg-slate-800"
                            aria-label="Cerrar sesion"
                        >
                            Cerrar sesion
                        </button>
                    </form>
                @endauth

                <p class="mt-3 text-center text-xs text-slate-500">Version {{ $appVersion }}</p>
            </div>
        </div>
    </aside>

    <div x-show="open" x-transition.opacity class="fixed inset-0 z-40 bg-slate-950/70 md:hidden" @click="open = false" aria-hidden="true"></div>

    <div class="md:pl-60">
        <header class="sticky top-0 z-30 border-b border-slate-800 bg-slate-900/90 backdrop-blur">
            <div class="flex items-start justify-between gap-4 px-4 py-4 md:px-8">
                <div class="flex items-start gap-3">
                    <button
                        type="button"
                        class="mt-1 inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-700 text-slate-200 transition hover:bg-slate-800 md:hidden"
                        @click="open = !open"
                        aria-label="Abrir menu lateral"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M2.5 5A.75.75 0 0 1 3.25 4h13.5a.75.75 0 0 1 0 1.5H3.25A.75.75 0 0 1 2.5 5Zm0 5a.75.75 0 0 1 .75-.75h13.5a.75.75 0 0 1 0 1.5H3.25A.75.75 0 0 1 2.5 10Zm.75 4.25a.75.75 0 0 0 0 1.5h13.5a.75.75 0 0 0 0-1.5H3.25Z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-300">@yield('section_label', 'Plataforma')</p>
                        <h2 class="mt-1 text-xl font-bold tracking-tight text-white">@yield('title', $pageTitle ?? 'Dashboard')</h2>

                        @php $breadcrumbs = $breadcrumbs ?? []; @endphp
                        <nav class="mt-2 flex flex-wrap items-center gap-2 text-xs text-slate-400" aria-label="Breadcrumb">
                            @forelse ($breadcrumbs as $breadcrumb)
                                @if (!$loop->first)
                                    <span class="text-slate-600">/</span>
                                @endif

                                @if (!empty($breadcrumb['url']))
                                    <a href="{{ $breadcrumb['url'] }}" class="transition hover:text-slate-200">{{ $breadcrumb['label'] }}</a>
                                @else
                                    <span class="font-semibold text-slate-200">{{ $breadcrumb['label'] }}</span>
                                @endif
                            @empty
                                <span class="font-semibold text-slate-200">Dashboard</span>
                            @endforelse
                        </nav>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div x-data="{ notifOpen: false }" class="relative">
                        <button type="button" @click="notifOpen = !notifOpen" class="relative inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-700 text-slate-200 transition hover:bg-slate-800" aria-label="Notificaciones de plataforma">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M10 2.5a4 4 0 0 0-4 4V8c0 .795-.316 1.558-.879 2.121l-.56.56A1.5 1.5 0 0 0 5.621 13h8.758a1.5 1.5 0 0 0 1.06-2.56l-.56-.56A3 3 0 0 1 14 8V6.5a4 4 0 0 0-4-4Z" />
                                <path d="M8 15.5a2 2 0 1 0 4 0H8Z" />
                            </svg>
                            @if ($notificationsCount > 0)
                                <span class="absolute -right-1 -top-1 inline-flex min-h-5 min-w-5 items-center justify-center rounded-full bg-indigo-500 px-1 text-[10px] font-bold text-white">{{ $notificationsCount }}</span>
                            @endif
                        </button>

                        <div x-show="notifOpen" @click.outside="notifOpen = false" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95 -translate-y-1" x-transition:enter-end="opacity-100 scale-100 translate-y-0" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100 translate-y-0" x-transition:leave-end="opacity-0 scale-95 -translate-y-1" class="absolute right-0 z-[60] mt-2 w-80 rounded-xl border border-slate-700/50 bg-slate-900 shadow-2xl shadow-slate-950/80">
                            <div class="flex items-center justify-between border-b border-slate-800 px-4 py-3">
                                <h3 class="text-sm font-bold text-white">Notificaciones</h3>
                                @if(($topbar_notifications_count ?? 0) > 0)
                                    <span class="rounded-full bg-indigo-500/15 px-2 py-0.5 text-[10px] font-bold text-indigo-200">{{ $topbar_notifications_count }} hoy</span>
                                @endif
                            </div>

                            <div class="max-h-80 overflow-y-auto">
                                @if(isset($notification_logs) && $notification_logs->count() > 0)
                                    @foreach($notification_logs->take(8) as $notification)
                                        @php
                                            $tipo = strtolower($notification->tipo ?? 'email');
                                            $estado = strtolower($notification->estado ?? 'enviado');
                                            $displayUser = trim(implode(' ', array_filter([$notification->user?->nombre, $notification->user?->apellidos])));
                                            $displayUser = $displayUser !== '' ? $displayUser : ($notification->user?->email ?? 'N/A');
                                            $tipoBadge = match($tipo) {
                                                'email' => 'bg-blue-500/15 text-blue-200',
                                                'whatsapp' => 'bg-emerald-500/15 text-emerald-200',
                                                'sms' => 'bg-orange-500/15 text-orange-200',
                                                default => 'bg-slate-600/20 text-slate-300',
                                            };
                                            $estadoDot = match($estado) {
                                                'enviado' => 'bg-emerald-400',
                                                'fallido' => 'bg-rose-400',
                                                'reintentado' => 'bg-amber-400',
                                                default => 'bg-slate-400',
                                            };
                                        @endphp
                                        <div class="border-b border-slate-800/50 px-4 py-3 transition hover:bg-slate-800/30 last:border-b-0">
                                            <div class="flex items-start justify-between gap-2">
                                                <div class="min-w-0 flex-1">
                                                    <p class="truncate text-xs font-semibold text-white">{{ ucfirst($notification->evento ?? 'Evento') }}</p>
                                                    <p class="mt-0.5 truncate text-[11px] text-slate-400">{{ $displayUser }}</p>
                                                    <p class="mt-1 text-[10px] text-slate-500">{{ optional($notification->created_at)->diffForHumans() }}</p>
                                                </div>
                                                <div class="flex shrink-0 flex-col items-end gap-1">
                                                    <span class="rounded-full px-1.5 py-0.5 text-[9px] font-semibold uppercase {{ $tipoBadge }}">{{ $tipo }}</span>
                                                    <span class="inline-flex items-center gap-1 text-[10px] text-slate-400">
                                                        <span class="h-1.5 w-1.5 rounded-full {{ $estadoDot }}"></span>
                                                        {{ $estado }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="flex flex-col items-center justify-center px-4 py-10 text-center">
                                        <svg class="h-8 w-8 text-slate-600" viewBox="0 0 20 20" fill="currentColor"><path d="M10 2.5a4 4 0 0 0-4 4V8c0 .795-.316 1.558-.879 2.121l-.56.56A1.5 1.5 0 0 0 5.621 13h8.758a1.5 1.5 0 0 0 1.06-2.56l-.56-.56A3 3 0 0 1 14 8V6.5a4 4 0 0 0-4-4Zm0 15a2.25 2.25 0 0 0 2.122-1.5H7.878A2.25 2.25 0 0 0 10 17.5Z" /></svg>
                                        <p class="mt-2 text-xs font-semibold text-slate-300">Sin notificaciones</p>
                                        <p class="mt-0.5 text-[11px] text-slate-500">Las notificaciones apareceran aqui.</p>
                                    </div>
                                @endif
                            </div>

                            <a href="{{ route('admin.dashboard', ['seccion' => 'notificaciones']) }}" class="block border-t border-slate-800 px-4 py-2.5 text-center text-xs font-semibold text-indigo-300 transition hover:bg-slate-800/50 hover:text-indigo-200">
                                Ver todas las notificaciones &rarr;
                            </a>
                        </div>
                    </div>

                    <div class="hidden items-center gap-2 rounded-full px-3 py-2 text-xs font-semibold ring-1 sm:flex {{ $systemHealthy ? 'bg-emerald-500/15 text-emerald-200 ring-emerald-500/40' : 'bg-rose-500/15 text-rose-200 ring-rose-500/40' }}">
                        <span class="inline-flex h-2 w-2 rounded-full {{ $systemHealthy ? 'bg-emerald-400' : 'bg-rose-400' }}"></span>
                        <span>{{ $systemHealthy ? 'Sistema OK' : "{$failedJobsTotal} jobs fallidos" }}</span>
                    </div>
                </div>
            </div>
        </header>

        <main class="px-4 pb-10 pt-6 md:px-8">
            @yield('content')
        </main>
    </div>
</div>

@stack('scripts')
</body>
</html>
