<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(403, 'No autenticado.');
        }

        $requiredPermissions = collect($permissions)->filter()->values();
        if ($requiredPermissions->isEmpty()) {
            return $next($request);
        }

        // Check if user has at least one of the required permissions
        $hasPermission = false;
        
        // Get user's role in current business
        $businessId = $user->current_business_id;
        if (!$businessId) {
            abort(403, 'No tienes un negocio activo asignado.');
        }

        // Check permissions through role
        if (Schema::hasTable('roles') && Schema::hasTable('permissions') && Schema::hasTable('role_permissions')) {
            $roleNameCol = Schema::hasColumn('roles', 'nombre') ? 'nombre' : 'name';
            $permNameCol = Schema::hasColumn('permissions', 'nombre') ? 'nombre' : 'name';
            
            $userPermissions = \Illuminate\Support\Facades\DB::table('business_user_roles as bur')
                ->join('roles as r', 'r.id', '=', 'bur.role_id')
                ->join('role_permissions as rp', 'rp.role_id', '=', 'r.id')
                ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
                ->where('bur.user_id', $user->id)
                ->where('bur.business_id', $businessId)
                ->whereIn('p.'.$permNameCol, $requiredPermissions)
                ->when(Schema::hasColumn('business_user_roles', 'deleted_at'), fn($q) => $q->whereNull('bur.deleted_at'))
                ->select('p.'.$permNameCol.' as permission_name')
                ->get()
                ->pluck('permission_name')
                ->toArray();

            $hasPermission = !empty($userPermissions);
        }

        if (!$hasPermission) {
            abort(403, 'No tienes permisos suficientes para realizar esta acción.');
        }

        return $next($request);
    }
}
