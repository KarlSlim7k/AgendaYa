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
        $this->command->info('🚀 Iniciando seeding de FASE 1...');
        $this->command->newLine();

        // 1. RBAC - Roles y Permisos (base del sistema)
        $this->command->info('📋 Paso 1/5: RBAC Sistema');
        $this->call(RolesAndPermissionsSeeder::class);
        $this->command->newLine();

        // 2. Platform Admin (superadmin global)
        $this->command->info('👤 Paso 2/5: Administradores de Plataforma');
        $this->call(PlatformAdminSeeder::class);
        $this->command->newLine();

        // 3. Negocios (tenants)
        $this->command->info('🏢 Paso 3/5: Negocios');
        $this->call(BusinessSeeder::class);
        $this->command->newLine();

        // 4. Sucursales
        $this->command->info('📍 Paso 4/5: Sucursales');
        $this->call(BusinessLocationSeeder::class);
        $this->command->newLine();

        // 5. Usuarios
        $this->command->info('👥 Paso 5/5: Usuarios');
        $this->call(UserSeeder::class);
        $this->command->newLine();

        // 6. Asignación de roles
        $this->command->info('🔐 Paso 6/6: Asignación de Roles');
        $this->call(BusinessUserRoleSeeder::class);
        $this->command->newLine();

        $this->command->info('✅ Seeding FASE 1 completado exitosamente!');
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
            ]
        );
        
        $this->command->newLine();
        $this->command->info('🔑 CREDENCIALES DE ACCESO:');
        $this->command->line('  Superadmin: admin@citasempresariales.com / password');
        $this->command->line('  Usuarios: carlos.martinez@example.com / password (y similares)');
        $this->command->newLine();
    }
}
