<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(403, 'No autenticado.');
        }

        $roleNames = collect($roles)->filter()->values();
        if ($roleNames->isEmpty()) {
            return $next($request);
        }

        $rolesTablesAvailable = Schema::hasTable('business_user_roles') && Schema::hasTable('roles');
        $roleNameColumn = null;
        if (Schema::hasTable('roles')) {
            if (Schema::hasColumn('roles', 'nombre')) {
                $roleNameColumn = 'nombre';
            } elseif (Schema::hasColumn('roles', 'name')) {
                $roleNameColumn = 'name';
            }
        }

        $hasRoleInContext = false;
        if ($rolesTablesAvailable && $roleNameColumn !== null) {
            $contextQuery = DB::table('business_user_roles as bur')
                ->join('roles as r', 'r.id', '=', 'bur.role_id')
                ->where('bur.user_id', $user->id)
                ->whereIn('r.'.$roleNameColumn, $roleNames);

            if (Schema::hasColumn('business_user_roles', 'deleted_at')) {
                $contextQuery->whereNull('bur.deleted_at');
            }

            if (Schema::hasColumn('business_user_roles', 'business_id')) {
                $contextQuery->where(function ($query) use ($user) {
                    $query->whereNull('bur.business_id');

                    if (!empty($user->current_business_id)) {
                        $query->orWhere('bur.business_id', $user->current_business_id);
                    }
                });
            }

            $hasRoleInContext = $contextQuery->exists();
        }

        // Compatibilidad: el esquema actual mantiene business_id como obligatorio.
        $hasAnyPlatformRole = false;
        if ($roleNames->contains('PLATAFORMA_ADMIN')) {
            if ($rolesTablesAvailable && $roleNameColumn !== null) {
                $platformRoleQuery = DB::table('business_user_roles as bur')
                    ->join('roles as r', 'r.id', '=', 'bur.role_id')
                    ->where('bur.user_id', $user->id)
                    ->where('r.'.$roleNameColumn, 'PLATAFORMA_ADMIN');

                if (Schema::hasColumn('business_user_roles', 'deleted_at')) {
                    $platformRoleQuery->whereNull('bur.deleted_at');
                }

                $hasAnyPlatformRole = $platformRoleQuery->exists();
            }

            if (!$hasAnyPlatformRole && !empty($user->email) && Schema::hasTable('platform_admins')) {
                $platformAdminQuery = DB::table('platform_admins')
                    ->where('email', $user->email);

                if (Schema::hasColumn('platform_admins', 'activo')) {
                    $platformAdminQuery->where('activo', true);
                }

                if (Schema::hasColumn('platform_admins', 'deleted_at')) {
                    $platformAdminQuery->whereNull('deleted_at');
                }

                $hasAnyPlatformRole = $platformAdminQuery->exists();
            }
        }

        if (!$hasRoleInContext && !$hasAnyPlatformRole) {
            abort(403, 'No tienes permisos para acceder a este modulo.');
        }

        return $next($request);
    }
}
