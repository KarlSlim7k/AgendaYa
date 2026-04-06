<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\BusinessUserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class NegocioAdminSeeder extends Seeder
{
    /**
     * Crea el usuario negocio@agendaya.mx con rol NEGOCIO_ADMIN
     * y lo asocia a un negocio aprobado.
     */
    public function run(): void
    {
        // Crear o actualizar el usuario de prueba
        $user = User::withTrashed()->where('email', 'negocio@agendaya.mx')->first();

        if ($user && $user->trashed()) {
            $user->restore();
        }

        if (!$user) {
            $user = User::create([
                'nombre'             => 'Admin Negocio',
                'apellidos'          => 'AgendaYa',
                'email'              => 'negocio@agendaya.mx',
                'telefono'           => '+525512340000',
                'password'           => Hash::make('password123'),
                'email_verified_at'  => now(),
            ]);
        } else {
            $user->update([
                'password'           => Hash::make('password123'),
                'email_verified_at'  => now(),
            ]);
        }

        // Obtener el rol NEGOCIO_ADMIN
        $adminRole = Role::where('nombre', 'NEGOCIO_ADMIN')->first();

        if (!$adminRole) {
            $this->command->error('❌ Rol NEGOCIO_ADMIN no encontrado. Ejecuta RolesAndPermissionsSeeder primero.');
            return;
        }

        // Usar el primer negocio aprobado disponible
        $business = Business::where('estado', 'approved')->first();

        if (!$business) {
            // Crear un negocio de prueba si no existe ninguno aprobado
            $business = Business::create([
                'nombre'      => 'Negocio AgendaYa',
                'razon_social'=> 'Negocio AgendaYa S.A. de C.V.',
                'rfc'         => 'NAY010101ZZZ',
                'telefono'    => '+525512340001',
                'email'       => 'contacto@negocio.agendaya.mx',
                'categoria'   => 'peluqueria',
                'descripcion' => 'Negocio de prueba para testing',
                'estado'      => 'approved',
            ]);
        }

        // Asignar rol NEGOCIO_ADMIN en ese negocio (ignorar si ya existe)
        BusinessUserRole::firstOrCreate(
            [
                'user_id'     => $user->id,
                'business_id' => $business->id,
                'role_id'     => $adminRole->id,
            ],
            [
                'assigned_by' => null,
                'asignado_el' => now(),
            ]
        );

        // Establecer el negocio activo en el usuario
        $user->update(['current_business_id' => $business->id]);

        $this->command->info("✅ Usuario negocio@agendaya.mx creado/actualizado");
        $this->command->info("   Negocio asignado: {$business->nombre} (ID: {$business->id})");
        $this->command->info("   Rol: NEGOCIO_ADMIN");
        $this->command->info("   Contraseña: password123");
    }
}
