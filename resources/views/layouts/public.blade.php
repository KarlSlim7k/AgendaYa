<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @include('partials.vite-assets')
    @livewireStyles
</head>
<body class="bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 text-slate-100 antialiased [font-family:Inter,sans-serif]">
    <div class="min-h-screen">
        {{-- Header --}}
        <header class="border-b border-slate-800/70 bg-slate-950/90 backdrop-blur">
            <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4 py-4 md:px-6">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-600 shadow-lg shadow-emerald-600/30">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-lg font-bold text-white">{{ config('app.name') }}</h1>
                    </div>
                </div>
                <a href="{{ url('/') }}" class="text-sm font-medium text-slate-400 transition hover:text-white">
                    Volver al inicio
                </a>
            </div>
        </header>

        {{-- Main Content --}}
        <main class="mx-auto max-w-5xl px-4 py-8 md:px-6 md:py-12">
            {{ $slot }}
        </main>

        {{-- Footer --}}
        <footer class="border-t border-slate-800/70 bg-slate-950/50 py-6 text-center text-xs text-slate-500">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.</p>
        </footer>
    </div>

    @livewireScripts
    @stack('scripts')
</body>
</html>
