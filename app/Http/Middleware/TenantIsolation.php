<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para validar que el recurso pertenece al tenant actual
 * 
 * Uso: Route::middleware('tenant.isolation')
 */
class TenantIsolation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Si el usuario tiene current_business_id, validar que está actuando dentro de su tenant
        if ($user && $user->current_business_id) {
            // Obtener el modelo desde la ruta (route model binding)
            $routeParameters = $request->route()->parameters();
            
            foreach ($routeParameters as $parameter) {
                // Si el parámetro es un modelo con business_id
                if (is_object($parameter) && property_exists($parameter, 'business_id')) {
                    // Validar que el recurso pertenece al tenant del usuario
                    if ($parameter->business_id !== $user->current_business_id) {
                        abort(403, 'No tienes acceso a este recurso. El recurso no pertenece a tu negocio.');
                    }
                }
            }
        }

        return $next($request);
    }
}
