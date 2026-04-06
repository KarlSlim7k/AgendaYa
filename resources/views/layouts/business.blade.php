<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', $title ?? $pageTitle ?? 'Dashboard') - {{ config('app.name', 'Citas Empresariales') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @include('partials.vite-assets')
    @livewireStyles
    @stack('head')
</head>
<body class="bg-slate-900 text-slate-100 antialiased [font-family:Inter,sans-serif]">
@php
    $currentSection = request()->routeIs('business.dashboard') ? 'dashboard' : (request()->routeIs('business.appointments.*') ? 'appointments' : (request()->routeIs('business.services.*') ? 'services' : (request()->routeIs('business.employees.*') ? 'employees' : (request()->routeIs('business.schedules.*') ? 'schedules' : (request()->routeIs('business.reports.*') ? 'reports' : (request()->routeIs('business.profile') ? 'profile' : 'dashboard'))))));

    $businessUser = auth()->user();
    $businessName = $businessUser?->currentBusiness?->nombre ?? 'Mi Negocio';
    $userName = trim(implode(' ', array_filter([
        $businessUser?->nombre ?? $businessUser?->name,
        $businessUser?->apellidos,
    ])));
    $userName = $userName !== '' ? $userName : 'Administrador';

    $initials = collect(explode(' ', $userName))
        ->filter()
        ->take(2)
        ->map(fn ($part) => strtoupper(mb_substr($part, 0, 1)))
        ->implode('');

    $activeLinkClass = 'flex items-center gap-3 rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white shadow-lg shadow-emerald-600/20';
    $baseLinkClass = 'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-300 transition hover:bg-slate-800 hover:text-white';
@endphp

<div x-data="{ open: false }" class="min-h-screen">
    <aside
        :class="open ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
        class="fixed inset-y-0 left-0 z-50 w-60 border-r border-slate-800 bg-slate-950/95 backdrop-blur transition-transform duration-300"
        aria-label="Sidebar del negocio"
    >
        <div class="flex h-full flex-col">
            <div class="border-b border-slate-800 px-5 py-6">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-300">Panel Negocio</p>
                <h1 class="mt-2 text-lg font-bold tracking-tight text-white">{{ $businessName }}</h1>
            </div>

            <nav class="flex-1 space-y-6 overflow-y-auto px-4 py-5" tabindex="0" aria-label="Navegacion principal">
                <section>
                    <p class="px-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Principal</p>
                    <div class="mt-3 space-y-1">
                        <a href="{{ route('business.dashboard') }}" class="{{ $currentSection === 'dashboard' ? $activeLinkClass : $baseLinkClass }}">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10.75 2.75a.75.75 0 0 0-1.5 0v5.5h-5.5a.75.75 0 0 0 0 1.5h5.5v5.5a.75.75 0 0 0 1.5 0v-5.5h5.5a.75.75 0 0 0 0-1.5h-5.5v-5.5Z" />
                            </svg>
                            <span>Dashboard</span>
                        </a>

                        <a href="{{ route('business.appointments.index') }}" class="{{ $currentSection === 'appointments' ? $activeLinkClass : $baseLinkClass }}">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.75A2.25 2.25 0 0 1 18 6.25v9.5A2.25 2.25 0 0 1 15.75 18h-11.5A2.25 2.25 0 0 1 2 15.75v-9.5A2.25 2.25 0 0 1 4.25 4H5V2.75A.75.75 0 0 1 5.75 2Z" />
                            </svg>
                            <span>Citas</span>
                        </a>

                        <a href="{{ route('business.services.index') }}" class="{{ $currentSection === 'services' ? $activeLinkClass : $baseLinkClass }}">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M11.983 1.907a.75.75 0 0 0-1.5 0L10.25 8H5.75a.75.75 0 0 0 0 1.5h4.5v6.593a.75.75 0 0 0 1.5 0V9.5h4.5a.75.75 0 0 0 0-1.5h-4.5V1.907Z" />
                            </svg>
                            <span>Servicios</span>
                        </a>

                        <a href="{{ route('business.employees.index') }}" class="{{ $currentSection === 'employees' ? $activeLinkClass : $baseLinkClass }}">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 2a4 4 0 1 0 0 8 4 4 0 0 0 0-8ZM4 14a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-2Z" />
                            </svg>
                            <span>Empleados</span>
                        </a>
                    </div>
                </section>

                <section>
                    <p class="px-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Gestion</p>
                    <div class="mt-3 space-y-1">
                        <a href="{{ route('business.schedules.index') }}" class="{{ $currentSection === 'schedules' ? $activeLinkClass : $baseLinkClass }}">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.75A2.25 2.25 0 0 1 18 6.25v9.5A2.25 2.25 0 0 1 15.75 18h-9.5A2.25 2.25 0 0 1 4 15.75v-9.5A2.25 2.25 0 0 1 6.25 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1.75 4.25v9.5c0 .414.336.75.75.75h9.5a.75.75 0 0 0 .75-.75v-9.5a.75.75 0 0 0-.75-.75h-9.5a.75.75 0 0 0-.75.75Z" clip-rule="evenodd" />
                            </svg>
                            <span>Horarios</span>
                        </a>

                        <a href="{{ route('business.reports.index') }}" class="{{ $currentSection === 'reports' ? $activeLinkClass : $baseLinkClass }}">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M2 5.75A2.75 2.75 0 0 1 4.75 3h10.5A2.75 2.75 0 0 1 18 5.75v8.5A2.75 2.75 0 0 1 15.25 17H4.75A2.75 2.75 0 0 1 2 14.25v-8.5Zm5.25 1.5a.75.75 0 0 0 0 1.5h5.5a.75.75 0 0 0 0-1.5h-5.5Zm0 3a.75.75 0 0 0 0 1.5h2.5a.75.75 0 0 0 0-1.5h-2.5Z" />
                            </svg>
                            <span>Reportes</span>
                        </a>

                        <a href="{{ route('business.profile') }}" class="{{ $currentSection === 'profile' ? $activeLinkClass : $baseLinkClass }}">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M7.84 1.804a1 1 0 0 1 1.32-.753l.163.058a1.75 1.75 0 0 0 1.354 0l.163-.058a1 1 0 0 1 1.32.753l.034.17a1.75 1.75 0 0 0 .99 1.224l.157.074a1 1 0 0 1 .43 1.404l-.088.15a1.75 1.75 0 0 0 0 1.748l.088.15a1 1 0 0 1-.43 1.404l-.157.074a1.75 1.75 0 0 0-.99 1.224l-.034.17a1 1 0 0 1-1.32.753l-.163-.058a1.75 1.75 0 0 0-1.354 0l-.163.058a1 1 0 0 1-1.32-.753l-.034-.17a1.75 1.75 0 0 0-.99-1.224l-.157-.074a1 1 0 0 1-.43-1.404l.088-.15a1.75 1.75 0 0 0 0-1.748l-.088-.15a1 1 0 0 1 .43-1.404l.157-.074a1.75 1.75 0 0 0 .99-1.224l.034-.17Zm2.16 7.696a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" clip-rule="evenodd" />
                            </svg>
                            <span>Perfil Negocio</span>
                        </a>
                    </div>
                </section>
            </nav>

            <div class="border-t border-slate-800 px-4 py-4">
                @auth
                    <div class="flex items-center gap-3 rounded-lg bg-slate-900 px-3 py-2">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-500/20 text-sm font-bold text-emerald-200">
                            {{ $initials !== '' ? $initials : 'AD' }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-white">{{ $userName }}</p>
                            <p class="truncate text-xs text-slate-400">NEGOCIO_ADMIN</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('logout') }}" class="mt-3">
                        @csrf
                        <button
                            type="submit"
                            class="w-full rounded-lg border border-slate-700 px-3 py-2 text-sm font-medium text-slate-200 transition hover:border-slate-500 hover:bg-slate-800"
                        >
                            Cerrar sesion
                        </button>
                    </form>
                @endauth

                <p class="mt-3 text-center text-xs text-slate-500">Version {{ config('app.version', env('APP_VERSION', '1.0.0')) }}</p>
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
                    >
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M2.5 5A.75.75 0 0 1 3.25 4h13.5a.75.75 0 0 1 0 1.5H3.25A.75.75 0 0 1 2.5 5Zm0 5a.75.75 0 0 1 .75-.75h13.5a.75.75 0 0 1 0 1.5H3.25A.75.75 0 0 1 2.5 10Zm.75 4.25a.75.75 0 0 0 0 1.5h13.5a.75.75 0 0 0 0-1.5H3.25Z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-300">@yield('section_label', $sectionLabel ?? 'Mi Negocio')</p>
                        <h2 class="mt-1 text-xl font-bold tracking-tight text-white">@yield('title', $title ?? 'Dashboard')</h2>
                    </div>
                </div>
            </div>
        </header>

        <main class="px-4 pb-10 pt-6 md:px-8">
            {{ $slot ?? '' }}
            @yield('content')
        </main>
    </div>
</div>

@livewireScripts
@stack('scripts')
</body>
</html>
