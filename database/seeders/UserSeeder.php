<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear 10 usuarios específicos con datos realistas
        $specificUsers = [
            [
                'nombre' => 'Carlos',
                'apellidos' => 'Martínez López',
                'email' => 'carlos.martinez@example.com',
                'telefono' => '+525512341001',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
            [
                'nombre' => 'María',
                'apellidos' => 'García Hernández',
                'email' => 'maria.garcia@example.com',
                'telefono' => '+525512341002',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
            [
                'nombre' => 'José',
                'apellidos' => 'Rodríguez Pérez',
                'email' => 'jose.rodriguez@example.com',
                'telefono' => '+525512341003',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
            [
                'nombre' => 'Ana',
                'apellidos' => 'López Sánchez',
                'email' => 'ana.lopez@example.com',
                'telefono' => '+525512341004',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
            [
                'nombre' => 'Luis',
                'apellidos' => 'González Ramírez',
                'email' => 'luis.gonzalez@example.com',
                'telefono' => '+525512341005',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
            [
                'nombre' => 'Laura',
                'apellidos' => 'Hernández Cruz',
                'email' => 'laura.hernandez@example.com',
                'telefono' => '+525512341006',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
            [
                'nombre' => 'Miguel',
                'apellidos' => 'Díaz Torres',
                'email' => 'miguel.diaz@example.com',
                'telefono' => '+525512341007',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
            [
                'nombre' => 'Sofía',
                'apellidos' => 'Morales Flores',
                'email' => 'sofia.morales@example.com',
                'telefono' => '+525512341008',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
            [
                'nombre' => 'Pedro',
                'apellidos' => 'Jiménez Ruiz',
                'email' => 'pedro.jimenez@example.com',
                'telefono' => '+525512341009',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
            [
                'nombre' => 'Isabel',
                'apellidos' => 'Mendoza Reyes',
                'email' => 'isabel.mendoza@example.com',
                'telefono' => '+525512341010',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        ];

        foreach ($specificUsers as $userData) {
            User::create($userData);
        }

        // Crear 25 usuarios adicionales con factory
        User::factory()->count(25)->create();

        $this->command->info('✅ 35 usuarios creados (10 específicos + 25 generados)');
    }
}
