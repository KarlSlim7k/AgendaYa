<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🚀 Iniciando seeding completo (FASE 1 + FASE 2)...');
        $this->command->newLine();

        // 1. RBAC - Roles y Permisos (base del sistema)
        $this->command->info('📋 Paso 1/10: RBAC Sistema');
        $this->call(RolesAndPermissionsSeeder::class);
        $this->command->newLine();

        // 2. Platform Admin (superadmin global)
        $this->command->info('👤 Paso 2/10: Administradores de Plataforma');
        $this->call(PlatformAdminSeeder::class);
        $this->command->newLine();

        // 3. Negocios (tenants)
        $this->command->info('🏢 Paso 3/10: Negocios');
        $this->call(BusinessSeeder::class);
        $this->command->newLine();

        // 4. Sucursales
        $this->command->info('📍 Paso 4/10: Sucursales');
        $this->call(BusinessLocationSeeder::class);
        $this->command->newLine();

        // 5. Usuarios
        $this->command->info('👥 Paso 5/10: Usuarios');
        $this->call(UserSeeder::class);
        $this->command->newLine();

        // 6. Asignación de roles
        $this->command->info('🔐 Paso 6/10: Asignación de Roles');
        $this->call(BusinessUserRoleSeeder::class);
        $this->command->newLine();

        // === FASE 2: CORE NEGOCIO ===

        // 7. Servicios
        $this->command->info('💼 Paso 7/10: Servicios');
        $this->call(ServiceSeeder::class);
        $this->command->newLine();

        // 8. Empleados
        $this->command->info('👷 Paso 8/10: Empleados');
        $this->call(EmployeeSeeder::class);
        $this->command->newLine();

        // 9. Asignación servicios-empleados
        $this->command->info('🔗 Paso 9/10: Asignación Servicios a Empleados');
        $this->call(EmployeeServiceSeeder::class);
        $this->command->newLine();

        // 10. Horarios base
        $this->command->info('⏰ Paso 10/10: Horarios Base');
        $this->call(ScheduleTemplateSeeder::class);
        $this->command->newLine();

        $this->command->info('✅ Seeding completo (FASE 1 + FASE 2) exitoso!');
        $this->command->newLine();
        
        $this->displaySummary();
    }

    /**
     * Mostrar resumen de datos creados.
     */
    private function displaySummary(): void
    {
        $this->command->info('📊 RESUMEN DE DATOS CREADOS:');
        $this->command->table(
            ['Entidad', 'Cantidad', 'Detalles'],
            [
                ['Roles', '5', 'USUARIO_FINAL, STAFF, MANAGER, ADMIN, PLATAFORMA_ADMIN'],
                ['Permisos', '26', '7 módulos (perfil, negocio, sucursal, servicio, empleado, agenda, cita, reportes)'],
                ['Asignaciones RBAC', '80', 'Matriz completa implementada'],
                ['Platform Admins', '1', 'admin@citasempresariales.com'],
                ['Negocios', '5', '4 aprobados, 1 pendiente (peluquería, clínica, taller, spa, consultorio)'],
                ['Sucursales', '11', '10 activas, 1 inactiva'],
                ['Usuarios', '35', '10 específicos + 25 generados'],
                ['Roles Asignados', '10', '4 ADMIN, 2 MANAGER, 4 STAFF'],
                ['Servicios', '~15', '2-3 servicios por negocio según categoría'],
                ['Empleados', '15', '3 empleados por negocio'],
                ['Asignaciones Servicio-Empleado', '~30', 'Empleados con servicios asignados'],
                ['Horarios Base', '77', '7 días × 11 sucursales (Lun-Vie 9-18, Sáb 9-14)'],
            ]
        );
        
        $this->command->newLine();
        $this->command->info('🔑 CREDENCIALES DE ACCESO:');
        $this->command->line('  Superadmin: admin@citasempresariales.com / password');
        $this->command->line('  Usuarios: carlos.martinez@example.com / password (y similares)');
        $this->command->newLine();
    }
}
