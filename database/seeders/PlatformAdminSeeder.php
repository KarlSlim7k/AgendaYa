<?php

namespace Database\Seeders;

use App\Models\PlatformAdmin;
use Illuminate\Database\Seeder;

class PlatformAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear superadmin principal
        PlatformAdmin::create([
            'nombre' => 'Administrador Principal',
            'email' => 'admin@citasempresariales.com',
            'password' => 'password', // Se hasheará automáticamente
            'activo' => true,
            'email_verified_at' => now(),
        ]);

        $this->command->info('✅ 1 Platform Admin creado');
    }
}
