<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // TODO: Implementar RBAC real basado en tabla permissions
        // Estos Gates temporales permiten todas las operaciones para desarrollo
        $permissions = [
            'servicio.read',
            'servicio.create', 
            'servicio.update',
            'servicio.delete',
            'empleado.read',
            'empleado.create',
            'empleado.update',
            'empleado.delete',
            'agenda.read',
            'agenda.create',
            'agenda.update',
            'agenda.delete',
            'cita.read',
            'cita.create',
            'cita.update',
            'cita.delete',
        ];

        foreach ($permissions as $permission) {
            Gate::define($permission, fn($user) => true);
        }
    }
}
