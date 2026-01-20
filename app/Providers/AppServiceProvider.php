<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\ViteManifestNotFoundException;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // #region agent log
        // Mejorar configuración de PDO para MariaDB
        $this->app->resolving('db', function ($db) {
            $db->listen(function ($query) {
                // Logging de consultas SQL para debug
                if (config('app.debug') || app()->environment('production')) {
                    Log::debug('SQL Query Debug', [
                        'sessionId' => 'debug-session',
                        'runId' => 'sql-query',
                        'hypothesisId' => 'F',
                        'location' => 'AppServiceProvider::register',
                        'message' => 'Consulta SQL ejecutada',
                        'data' => [
                            'sql' => $query->sql,
                            'bindings' => $query->bindings,
                            'time' => $query->time,
                            'connection' => $query->connectionName,
                        ],
                        'timestamp' => now()->timestamp * 1000,
                    ]);
                }
            });
        });
        // #endregion
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

        // #region agent log
        // Instrumentación para debug: Verificar configuración de Vite
        try {
            $viteInstance = app(\Illuminate\Foundation\Vite::class);
            $buildDir = 'build';
            $manifestPath = public_path($buildDir . '/manifest.json');
            
            Log::info('Vite Debug - Verificando manifest', [
                'sessionId' => 'debug-session',
                'runId' => 'initial',
                'hypothesisId' => 'A',
                'location' => 'AppServiceProvider::boot',
                'message' => 'Verificando ruta del manifest',
                'data' => [
                    'public_path' => public_path(),
                    'build_directory' => $buildDir,
                    'manifest_path' => $manifestPath,
                    'manifest_exists' => file_exists($manifestPath),
                    'manifest_readable' => is_readable($manifestPath),
                    'public_dir_exists' => is_dir(public_path()),
                    'build_dir_exists' => is_dir(public_path($buildDir)),
                ],
                'timestamp' => now()->timestamp * 1000,
            ]);
        } catch (\Exception $e) {
            Log::error('Vite Debug - Error al verificar manifest', [
                'sessionId' => 'debug-session',
                'runId' => 'initial',
                'hypothesisId' => 'A',
                'location' => 'AppServiceProvider::boot',
                'message' => 'Error al verificar manifest',
                'data' => [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ],
                'timestamp' => now()->timestamp * 1000,
            ]);
        }
        // #endregion
    }
}
