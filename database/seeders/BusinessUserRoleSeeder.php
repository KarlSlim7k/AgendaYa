<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\BusinessUserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class BusinessUserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $businesses = Business::where('estado', 'approved')->get();
        $users = User::limit(10)->get(); // Primeros 10 usuarios
        
        $roleUsuarioFinal = Role::where('nombre', 'USUARIO_FINAL')->first();
        $roleStaff = Role::where('nombre', 'NEGOCIO_STAFF')->first();
        $roleManager = Role::where('nombre', 'NEGOCIO_MANAGER')->first();
        $roleAdmin = Role::where('nombre', 'NEGOCIO_ADMIN')->first();

        $assignedCount = 0;

        // Asignar NEGOCIO_ADMIN a cada negocio (primeros 4 usuarios)
        foreach ($businesses->take(4) as $index => $business) {
            BusinessUserRole::create([
                'user_id' => $users[$index]->id,
                'business_id' => $business->id,
                'role_id' => $roleAdmin->id,
                'assigned_by' => null, // Auto-asignado
                'asignado_el' => now()->subDays(rand(30, 90)),
            ]);
            $assignedCount++;
        }

        // Asignar NEGOCIO_MANAGER a algunas sucursales (usuarios 5-6)
        $estilos = $businesses->where('nombre', 'Estilos Modernos')->first();
        if ($estilos) {
            BusinessUserRole::create([
                'user_id' => $users[4]->id,
                'business_id' => $estilos->id,
                'role_id' => $roleManager->id,
                'assigned_by' => $users[0]->id, // Asignado por el admin
                'asignado_el' => now()->subDays(rand(15, 45)),
            ]);
            $assignedCount++;
        }

        $clinica = $businesses->where('nombre', 'Clínica San Rafael')->first();
        if ($clinica) {
            BusinessUserRole::create([
                'user_id' => $users[5]->id,
                'business_id' => $clinica->id,
                'role_id' => $roleManager->id,
                'assigned_by' => $users[1]->id,
                'asignado_el' => now()->subDays(rand(15, 45)),
            ]);
            $assignedCount++;
        }

        // Asignar NEGOCIO_STAFF a empleados (usuarios 7-10)
        $taller = $businesses->where('nombre', 'Taller Mecánico Rodríguez')->first();
        if ($taller) {
            BusinessUserRole::create([
                'user_id' => $users[6]->id,
                'business_id' => $taller->id,
                'role_id' => $roleStaff->id,
                'assigned_by' => $users[2]->id,
                'asignado_el' => now()->subDays(rand(5, 30)),
            ]);
            $assignedCount++;

            BusinessUserRole::create([
                'user_id' => $users[7]->id,
                'business_id' => $taller->id,
                'role_id' => $roleStaff->id,
                'assigned_by' => $users[2]->id,
                'asignado_el' => now()->subDays(rand(5, 30)),
            ]);
            $assignedCount++;
        }

        $spa = $businesses->where('nombre', 'Spa Relax Total')->first();
        if ($spa) {
            BusinessUserRole::create([
                'user_id' => $users[8]->id,
                'business_id' => $spa->id,
                'role_id' => $roleStaff->id,
                'assigned_by' => $users[3]->id,
                'asignado_el' => now()->subDays(rand(5, 30)),
            ]);
            $assignedCount++;

            BusinessUserRole::create([
                'user_id' => $users[9]->id,
                'business_id' => $spa->id,
                'role_id' => $roleStaff->id,
                'assigned_by' => $users[3]->id,
                'asignado_el' => now()->subDays(rand(5, 30)),
            ]);
            $assignedCount++;
        }

        // Los usuarios restantes (11-35) son USUARIO_FINAL sin asignación a negocios
        // (tendrán este rol por defecto cuando reserven su primera cita)

        $this->command->info("✅ {$assignedCount} roles asignados (4 ADMIN, 2 MANAGER, 4 STAFF)");
        $this->command->info('   25 usuarios restantes como USUARIO_FINAL (sin asignación explícita)');
    }
}
