<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Laravel</title>
        @include('partials.vite-assets')
    </head>
    <body class="antialiased">
        <div class="relative sm:flex sm:justify-center sm:items-center min-h-screen bg-gray-100 selection:bg-primary-500 selection:text-white">
            <div class="max-w-7xl mx-auto p-6 lg:p-8">
                <div class="flex justify-center">
                    <h1 class="text-4xl font-bold text-gray-900">CitasEmpresariales</h1>
                </div>
                <div class="mt-8">
                    <p class="text-center text-gray-600">Plataforma SaaS Multi-Tenant para Gestión de Citas</p>
                </div>
            </div>
        </div>
    </body>
</html>
