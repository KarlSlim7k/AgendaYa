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
<body class="bg-[#0b0f1a] text-slate-100 antialiased [font-family:Inter,sans-serif]">
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

    $activeLinkClass = 'flex items-center gap-3 rounded-xl bg-emerald-600/20 px-3 py-2.5 text-sm font-semibold text-emerald-300 ring-1 ring-emerald-500/30';
    $baseLinkClass = 'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-400 transition hover:bg-slate-800/60 hover:text-slate-200';
@endphp

<div x-data="{ open: false }" class="min-h-screen">
    <aside
        :class="open ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
        class="fixed inset-y-0 left-0 z-50 w-60 border-r border-slate-800/80 bg-slate-950 transition-transform duration-300"
        aria-label="Sidebar del negocio"
    >
        <div class="flex h-full flex-col">
            <div class="border-b border-slate-800/80 px-5 py-5">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-600 shadow-lg shadow-emerald-600/30">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-emerald-400">Panel Negocio</p>
                        <h1 class="truncate text-sm font-bold text-white">{{ $businessName }}</h1>
                    </div>
                </div>
            </div>

            <nav class="flex-1 space-y-5 overflow-y-auto px-3 py-4" tabindex="0" aria-label="Navegacion principal">
                <section>
                    <p class="px-3 text-[10px] font-bold uppercase tracking-[0.2em] text-slate-600">Principal</p>
                    <div class="mt-2 space-y-0.5">
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
                    <p class="px-3 text-[10px] font-bold uppercase tracking-[0.2em] text-slate-600">Gestion</p>
                    <div class="mt-2 space-y-0.5">
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

            <div class="border-t border-slate-800/80 px-3 py-4 space-y-3">
                @auth
                    <div class="flex items-center gap-3 rounded-xl bg-slate-900 px-3 py-2.5 ring-1 ring-slate-800">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-600/20 text-sm font-bold text-emerald-300">
                            {{ $initials !== '' ? $initials : 'AD' }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-white">{{ $userName }}</p>
                            <p class="truncate text-[10px] uppercase tracking-wide text-slate-500">Administrador</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="flex w-full items-center justify-center gap-2 rounded-xl border border-slate-800 px-3 py-2 text-xs font-medium text-slate-400 transition hover:border-rose-500/30 hover:bg-rose-500/10 hover:text-rose-300"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            Cerrar sesion
                        </button>
                    </form>
                @endauth

                <p class="text-center text-[10px] text-slate-700">v{{ config('app.version', env('APP_VERSION', '1.0.0')) }}</p>
            </div>
        </div>
    </aside>

    <div x-show="open" x-transition.opacity class="fixed inset-0 z-40 bg-slate-950/70 md:hidden" @click="open = false" aria-hidden="true"></div>

    <div class="flex min-h-screen flex-col md:pl-60">
        <header class="sticky top-0 z-30 border-b border-slate-800/70 bg-[#0b0f1a]/90 backdrop-blur">
            <div class="flex items-center justify-between gap-4 px-4 py-3.5 md:px-6">
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-800 text-slate-400 transition hover:bg-slate-800 hover:text-white md:hidden"
                        @click="open = !open"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M2.5 5A.75.75 0 0 1 3.25 4h13.5a.75.75 0 0 1 0 1.5H3.25A.75.75 0 0 1 2.5 5Zm0 5a.75.75 0 0 1 .75-.75h13.5a.75.75 0 0 1 0 1.5H3.25A.75.75 0 0 1 2.5 10Zm.75 4.25a.75.75 0 0 0 0 1.5h13.5a.75.75 0 0 0 0-1.5H3.25Z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-emerald-400">@yield('section_label', $sectionLabel ?? 'Mi Negocio')</p>
                        <h2 class="text-lg font-bold tracking-tight text-white">@yield('title', $title ?? 'Dashboard')</h2>
                    </div>
                </div>
            </div>
        </header>

        <main class="w-full max-w-7xl px-4 pb-12 pt-6 md:mx-auto md:px-6">
            {{ $slot ?? '' }}
            @yield('content')
        </main>
    </div>
</div>

@livewireScripts
@stack('scripts')
</body>
</html>
