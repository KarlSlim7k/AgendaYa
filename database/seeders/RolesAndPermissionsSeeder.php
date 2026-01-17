<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // Crear permisos
            $permissions = $this->createPermissions();
            
            // Crear roles
            $roles = $this->createRoles();
            
            // Asignar permisos a roles según matriz RBAC
            $this->assignPermissionsToRoles($roles, $permissions);
        });

        $this->command->info('✅ RBAC: 5 roles + 26 permisos creados y asignados');
    }

    /**
     * Crear 26 permisos granulares.
     */
    private function createPermissions(): array
    {
        $permissionsData = [
            // Perfil (3)
            ['nombre' => 'perfil.create', 'display_name' => 'Crear Perfil', 'descripcion' => 'Crear perfil propio', 'modulo' => 'perfil', 'accion' => 'create'],
            ['nombre' => 'perfil.read', 'display_name' => 'Leer Perfil', 'descripcion' => 'Leer perfil', 'modulo' => 'perfil', 'accion' => 'read'],
            ['nombre' => 'perfil.update', 'display_name' => 'Actualizar Perfil', 'descripcion' => 'Actualizar perfil', 'modulo' => 'perfil', 'accion' => 'update'],
            
            // Negocio (4)
            ['nombre' => 'negocio.create', 'display_name' => 'Crear Negocio', 'descripcion' => 'Crear negocio', 'modulo' => 'negocio', 'accion' => 'create'],
            ['nombre' => 'negocio.read', 'display_name' => 'Leer Negocio', 'descripcion' => 'Leer información negocio', 'modulo' => 'negocio', 'accion' => 'read'],
            ['nombre' => 'negocio.update', 'display_name' => 'Actualizar Negocio', 'descripcion' => 'Actualizar información negocio', 'modulo' => 'negocio', 'accion' => 'update'],
            ['nombre' => 'negocio.delete', 'display_name' => 'Eliminar Negocio', 'descripcion' => 'Eliminar negocio', 'modulo' => 'negocio', 'accion' => 'delete'],
            
            // Sucursal (4)
            ['nombre' => 'sucursal.create', 'display_name' => 'Crear Sucursal', 'descripcion' => 'Crear sucursal', 'modulo' => 'sucursal', 'accion' => 'create'],
            ['nombre' => 'sucursal.read', 'display_name' => 'Leer Sucursal', 'descripcion' => 'Leer sucursal', 'modulo' => 'sucursal', 'accion' => 'read'],
            ['nombre' => 'sucursal.update', 'display_name' => 'Actualizar Sucursal', 'descripcion' => 'Actualizar sucursal', 'modulo' => 'sucursal', 'accion' => 'update'],
            ['nombre' => 'sucursal.delete', 'display_name' => 'Eliminar Sucursal', 'descripcion' => 'Eliminar sucursal', 'modulo' => 'sucursal', 'accion' => 'delete'],
            
            // Servicio (4)
            ['nombre' => 'servicio.create', 'display_name' => 'Crear Servicio', 'descripcion' => 'Crear servicio', 'modulo' => 'servicio', 'accion' => 'create'],
            ['nombre' => 'servicio.read', 'display_name' => 'Leer Servicio', 'descripcion' => 'Leer servicio', 'modulo' => 'servicio', 'accion' => 'read'],
            ['nombre' => 'servicio.update', 'display_name' => 'Actualizar Servicio', 'descripcion' => 'Actualizar servicio', 'modulo' => 'servicio', 'accion' => 'update'],
            ['nombre' => 'servicio.delete', 'display_name' => 'Eliminar Servicio', 'descripcion' => 'Eliminar servicio', 'modulo' => 'servicio', 'accion' => 'delete'],
            
            // Empleado (4)
            ['nombre' => 'empleado.create', 'display_name' => 'Crear Empleado', 'descripcion' => 'Crear empleado', 'modulo' => 'empleado', 'accion' => 'create'],
            ['nombre' => 'empleado.read', 'display_name' => 'Leer Empleado', 'descripcion' => 'Leer empleado', 'modulo' => 'empleado', 'accion' => 'read'],
            ['nombre' => 'empleado.update', 'display_name' => 'Actualizar Empleado', 'descripcion' => 'Actualizar empleado', 'modulo' => 'empleado', 'accion' => 'update'],
            ['nombre' => 'empleado.delete', 'display_name' => 'Eliminar Empleado', 'descripcion' => 'Eliminar empleado', 'modulo' => 'empleado', 'accion' => 'delete'],
            
            // Agenda (2)
            ['nombre' => 'agenda.create', 'display_name' => 'Crear Slot', 'descripcion' => 'Crear/reservar cita (slot)', 'modulo' => 'agenda', 'accion' => 'create'],
            ['nombre' => 'agenda.read', 'display_name' => 'Ver Disponibilidad', 'descripcion' => 'Ver disponibilidad', 'modulo' => 'agenda', 'accion' => 'read'],
            
            // Cita (4)
            ['nombre' => 'cita.create', 'display_name' => 'Crear Cita', 'descripcion' => 'Crear cita', 'modulo' => 'cita', 'accion' => 'create'],
            ['nombre' => 'cita.read', 'display_name' => 'Leer Cita', 'descripcion' => 'Leer cita', 'modulo' => 'cita', 'accion' => 'read'],
            ['nombre' => 'cita.update', 'display_name' => 'Actualizar Cita', 'descripcion' => 'Actualizar cita', 'modulo' => 'cita', 'accion' => 'update'],
            ['nombre' => 'cita.delete', 'display_name' => 'Eliminar Cita', 'descripcion' => 'Eliminar cita', 'modulo' => 'cita', 'accion' => 'delete'],
            
            // Reportes (1)
            ['nombre' => 'reportes.read', 'display_name' => 'Leer Reportes', 'descripcion' => 'Leer reportes financieros', 'modulo' => 'reportes', 'accion' => 'read'],
        ];

        $permissions = [];
        foreach ($permissionsData as $permData) {
            $permissions[$permData['nombre']] = Permission::create($permData);
        }

        return $permissions;
    }

    /**
     * Crear 5 roles jerárquicos.
     */
    private function createRoles(): array
    {
        $rolesData = [
            [
                'nombre' => 'USUARIO_FINAL',
                'display_name' => 'Usuario Final',
                'descripcion' => 'Usuario de app móvil, solo citas propias',
                'nivel_jerarquia' => 0,
            ],
            [
                'nombre' => 'NEGOCIO_STAFF',
                'display_name' => 'Staff del Negocio',
                'descripcion' => 'Empleado, ve su agenda/servicios asignados',
                'nivel_jerarquia' => 1,
            ],
            [
                'nombre' => 'NEGOCIO_MANAGER',
                'display_name' => 'Gerente del Negocio',
                'descripcion' => 'Gerente, CRUD de su sucursal asignada',
                'nivel_jerarquia' => 2,
            ],
            [
                'nombre' => 'NEGOCIO_ADMIN',
                'display_name' => 'Administrador del Negocio',
                'descripcion' => 'Admin del tenant, CRUD completo del negocio',
                'nivel_jerarquia' => 3,
            ],
            [
                'nombre' => 'PLATAFORMA_ADMIN',
                'display_name' => 'Administrador de Plataforma',
                'descripcion' => 'Superadmin, acceso total sin filtros',
                'nivel_jerarquia' => 4,
            ],
        ];

        $roles = [];
        foreach ($rolesData as $roleData) {
            $roles[$roleData['nombre']] = Role::create($roleData);
        }

        return $roles;
    }

    /**
     * Asignar permisos a roles según matriz RBAC.
     */
    private function assignPermissionsToRoles(array $roles, array $permissions): void
    {
        // USUARIO_FINAL (9 permisos)
        $roles['USUARIO_FINAL']->permissions()->attach([
            $permissions['perfil.create']->id,
            $permissions['perfil.read']->id,
            $permissions['perfil.update']->id,
            $permissions['servicio.read']->id,
            $permissions['empleado.read']->id,
            $permissions['agenda.read']->id,
            $permissions['cita.create']->id,
            $permissions['cita.read']->id,
            $permissions['cita.update']->id, // Solo cancelar propias
        ]);

        // NEGOCIO_STAFF (7 permisos)
        $roles['NEGOCIO_STAFF']->permissions()->attach([
            $permissions['perfil.create']->id,
            $permissions['perfil.read']->id,
            $permissions['perfil.update']->id,
            $permissions['servicio.read']->id, // Solo asignados
            $permissions['agenda.read']->id, // Solo propia
            $permissions['cita.read']->id, // Solo asignadas
            $permissions['cita.update']->id, // Cambiar estado asignadas
        ]);

        // NEGOCIO_MANAGER (18 permisos)
        $roles['NEGOCIO_MANAGER']->permissions()->attach([
            $permissions['perfil.create']->id,
            $permissions['perfil.read']->id,
            $permissions['perfil.update']->id,
            $permissions['negocio.read']->id,
            $permissions['sucursal.read']->id,
            $permissions['sucursal.update']->id,
            $permissions['servicio.read']->id,
            $permissions['empleado.create']->id,
            $permissions['empleado.read']->id,
            $permissions['empleado.update']->id,
            $permissions['empleado.delete']->id,
            $permissions['agenda.read']->id,
            $permissions['cita.read']->id,
            $permissions['cita.update']->id,
            $permissions['reportes.read']->id,
        ]);

        // NEGOCIO_ADMIN (24 permisos - casi todos excepto negocio.create y plataforma)
        $roles['NEGOCIO_ADMIN']->permissions()->attach([
            $permissions['perfil.create']->id,
            $permissions['perfil.read']->id,
            $permissions['perfil.update']->id,
            $permissions['negocio.read']->id,
            $permissions['negocio.update']->id,
            $permissions['negocio.delete']->id,
            $permissions['sucursal.create']->id,
            $permissions['sucursal.read']->id,
            $permissions['sucursal.update']->id,
            $permissions['sucursal.delete']->id,
            $permissions['servicio.create']->id,
            $permissions['servicio.read']->id,
            $permissions['servicio.update']->id,
            $permissions['servicio.delete']->id,
            $permissions['empleado.create']->id,
            $permissions['empleado.read']->id,
            $permissions['empleado.update']->id,
            $permissions['empleado.delete']->id,
            $permissions['agenda.read']->id,
            $permissions['cita.read']->id,
            $permissions['cita.update']->id,
            $permissions['cita.delete']->id,
            $permissions['reportes.read']->id,
        ]);

        // PLATAFORMA_ADMIN (26 permisos - TODOS)
        $roles['PLATAFORMA_ADMIN']->permissions()->attach(
            collect($permissions)->pluck('id')->toArray()
        );
    }
}
