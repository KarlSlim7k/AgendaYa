<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // #region agent log
        // Manejar errores de conexión a base de datos
        $exceptions->render(function (\PDOException $e, $request) {
            // Logging detallado para errores de BD
            if (str_contains($e->getMessage(), 'Access denied') || str_contains($e->getMessage(), 'SQLSTATE[28000]')) {
                \Illuminate\Support\Facades\Log::error('Database Connection Error', [
                    'sessionId' => 'debug-session',
                    'runId' => 'exception-handler',
                    'hypothesisId' => 'E',
                    'location' => 'bootstrap/app.php::withExceptions',
                    'message' => 'Error de conexión a base de datos',
                    'data' => [
                        'exception_message' => $e->getMessage(),
                        'error_code' => $e->getCode(),
                        'sql_state' => $e->errorInfo[0] ?? 'unknown',
                        'driver_code' => $e->errorInfo[1] ?? 'unknown',
                        'driver_message' => $e->errorInfo[2] ?? 'unknown',
                        'db_config' => [
                            'host' => config('database.connections.mysql.host'),
                            'port' => config('database.connections.mysql.port'),
                            'database' => config('database.connections.mysql.database'),
                            'username' => config('database.connections.mysql.username'),
                            'password_set' => !empty(config('database.connections.mysql.password')),
                        ],
                        'request_uri' => $request->getRequestUri(),
                        'request_method' => $request->getMethod(),
                    ],
                    'timestamp' => now()->timestamp * 1000,
                ]);
            }
            
            // Re-lanzar la excepción para que Laravel la maneje normalmente
            throw $e;
        });

        // Manejar error de Vite manifest no encontrado
        $exceptions->render(function (\Illuminate\Foundation\ViteManifestNotFoundException $e, $request) {
            // Logging detallado para debug
            \Illuminate\Support\Facades\Log::error('Vite Manifest Not Found', [
                'sessionId' => 'debug-session',
                'runId' => 'exception-handler',
                'hypothesisId' => 'B',
                'location' => 'bootstrap/app.php::withExceptions',
                'message' => 'ViteManifestNotFoundException capturada',
                'data' => [
                    'exception_message' => $e->getMessage(),
                    'public_path' => public_path(),
                    'build_dir' => 'build',
                    'manifest_path' => public_path('build/manifest.json'),
                    'manifest_exists' => file_exists(public_path('build/manifest.json')),
                    'public_dir_exists' => is_dir(public_path()),
                    'build_dir_exists' => is_dir(public_path('build')),
                    'public_dir_writable' => is_writable(public_path()),
                    'build_dir_writable' => is_writable(public_path('build')),
                    'request_uri' => $request->getRequestUri(),
                    'request_method' => $request->getMethod(),
                ],
                'timestamp' => now()->timestamp * 1000,
            ]);

            // En producción, retornar una respuesta más amigable
            if (app()->environment('production')) {
                // Crear un manifest.json mínimo si no existe
                $manifestPath = public_path('build/manifest.json');
                $buildDir = public_path('build');
                
                if (!is_dir($buildDir)) {
                    @mkdir($buildDir, 0755, true);
                }
                
                if (!file_exists($manifestPath)) {
                    // Crear manifest mínimo temporal
                    $minimalManifest = json_encode([
                        'resources/css/app.css' => [
                            'file' => 'assets/app.css',
                            'src' => 'resources/css/app.css',
                            'isEntry' => true,
                        ],
                        'resources/js/app.js' => [
                            'file' => 'assets/app.js',
                            'src' => 'resources/js/app.js',
                            'isEntry' => true,
                        ],
                    ], JSON_PRETTY_PRINT);
                    
                    @file_put_contents($manifestPath, $minimalManifest);
                    
                    \Illuminate\Support\Facades\Log::info('Vite Debug - Manifest temporal creado', [
                        'sessionId' => 'debug-session',
                        'runId' => 'exception-handler',
                        'hypothesisId' => 'B',
                        'location' => 'bootstrap/app.php::withExceptions',
                        'message' => 'Manifest temporal creado',
                        'data' => [
                            'manifest_path' => $manifestPath,
                            'manifest_created' => file_exists($manifestPath),
                        ],
                        'timestamp' => now()->timestamp * 1000,
                    ]);
                    
                    // Reintentar renderizar la vista
                    try {
                        return redirect()->back();
                    } catch (\Exception $retryException) {
                        // Si falla el redirect, mostrar página de error
                        return response()->view('errors.500', [
                            'message' => 'Error al cargar recursos. Por favor, ejecute "npm run build" en el servidor.',
                        ], 500);
                    }
                }
            }
            
            // Re-lanzar la excepción para que Laravel la maneje normalmente
            throw $e;
        });
        // #endregion
    })->create();
