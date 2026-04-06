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
            $hasCurrentBusinessId = Schema::hasColumn('users', 'current_business_id');

            // Si current_business_id es null o no existe la columna, auto-asignar desde business_user_roles
            if (Schema::hasColumn('business_user_roles', 'business_id')) {
                $effectiveBusinessId = ($hasCurrentBusinessId && !empty($user->current_business_id))
                    ? $user->current_business_id
                    : null;

                // Verificar que el negocio activo exista y no esté eliminado
                if (!empty($effectiveBusinessId) && Schema::hasTable('businesses')) {
                    $businessExistsQuery = DB::table('businesses')->where('id', $effectiveBusinessId);
                    if (Schema::hasColumn('businesses', 'deleted_at')) {
                        $businessExistsQuery->whereNull('deleted_at');
                    }
                    if (!$businessExistsQuery->exists()) {
                        $effectiveBusinessId = null;
                        if ($hasCurrentBusinessId) {
                            DB::table('users')->where('id', $user->id)->update(['current_business_id' => null]);
                            $user->current_business_id = null;
                            $user->unsetRelation('currentBusiness');
                        }
                    }
                }

                if (empty($effectiveBusinessId)) {
                    $firstBusinessRow = DB::table('business_user_roles as bur')
                        ->join('roles as r', 'r.id', '=', 'bur.role_id')
                        ->where('bur.user_id', $user->id)
                        ->whereIn('r.'.$roleNameColumn, $roleNames)
                        ->when(Schema::hasColumn('business_user_roles', 'deleted_at'), fn($q) => $q->whereNull('bur.deleted_at'))
                        ->select('bur.business_id')
                        ->first();

                    if ($firstBusinessRow) {
                        $effectiveBusinessId = $firstBusinessRow->business_id;

                        // Persistir solo si la columna existe en la BD
                        if ($hasCurrentBusinessId) {
                            DB::table('users')
                                ->where('id', $user->id)
                                ->update(['current_business_id' => $effectiveBusinessId]);
                            $user->current_business_id = $effectiveBusinessId;
                            $user->unsetRelation('currentBusiness');
                        }
                    }
                }
            } else {
                $effectiveBusinessId = null;
            }

            $contextQuery = DB::table('business_user_roles as bur')
                ->join('roles as r', 'r.id', '=', 'bur.role_id')
                ->where('bur.user_id', $user->id)
                ->whereIn('r.'.$roleNameColumn, $roleNames);

            if (Schema::hasColumn('business_user_roles', 'deleted_at')) {
                $contextQuery->whereNull('bur.deleted_at');
            }

            if (!empty($effectiveBusinessId)) {
                $contextQuery->where('bur.business_id', $effectiveBusinessId);
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

            if (!$hasAnyPlatformRole && Schema::hasTable('platform_admins')) {
                $platformAdminQuery = DB::table('platform_admins');

                if (Schema::hasColumn('platform_admins', 'email')) {
                    $platformAdminQuery->where('email', $user->email);
                } elseif (Schema::hasColumn('platform_admins', 'user_id')) {
                    $platformAdminQuery->where('user_id', $user->id);
                } else {
                    $platformAdminQuery = null;
                }

                if ($platformAdminQuery !== null) {
                    if (Schema::hasColumn('platform_admins', 'activo')) {
                        $platformAdminQuery->where('activo', true);
                    }
                    if (Schema::hasColumn('platform_admins', 'deleted_at')) {
                        $platformAdminQuery->whereNull('deleted_at');
                    }
                    $hasAnyPlatformRole = $platformAdminQuery->exists();
                }
            }
        }

        if (!$hasRoleInContext && !$hasAnyPlatformRole) {
            abort(403, 'No tienes permisos para acceder a este modulo.');
        }

        return $next($request);
    }
}
