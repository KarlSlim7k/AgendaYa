<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\Service;
use App\Models\Employee;
use App\Models\ScheduleTemplate;
use App\Models\Role;
use App\Models\BusinessUserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUserWithBusinessSeeder extends Seeder
{
    public function run(): void
    {
        // Crear usuario test
        $user = User::firstOrCreate(
            ['email' => 'test@citasempresariales.com'],
            [
                'nombre' => 'Usuario Test',
                'telefono' => '+525512345678',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Crear negocio
        $business = Business::firstOrCreate(
            ['nombre' => 'Negocio Test'],
            [
                'razon_social' => 'Negocio Test SA de CV',
                'rfc' => 'NTE123456ABC',
                'telefono' => '+525512345678',
                'email' => 'negocio@test.com',
                'categoria' => 'peluqueria',
                'estado' => 'approved',
            ]
        );

        // Asignar rol de admin al usuario
        $adminRole = Role::where('nombre', 'NEGOCIO_ADMIN')->first();
        BusinessUserRole::firstOrCreate([
            'user_id' => $user->id,
            'business_id' => $business->id,
            'role_id' => $adminRole->id,
        ]);

        // Actualizar current_business_id del usuario
        $user->update(['current_business_id' => $business->id]);

        // Crear sucursal
        $location = BusinessLocation::firstOrCreate(
            [
                'business_id' => $business->id,
                'nombre' => 'Sucursal Centro'
            ],
            [
                'direccion' => 'Calle Principal 123',
                'ciudad' => 'Ciudad de México',
                'estado' => 'CDMX',
                'codigo_postal' => '01000',
                'telefono' => '+525512345678',
                'zona_horaria' => 'America/Mexico_City',
            ]
        );

        // Crear servicios
        $servicios = [
            ['nombre' => 'Corte de Cabello', 'duracion_minutos' => 30, 'precio' => 150.00],
            ['nombre' => 'Tinte', 'duracion_minutos' => 60, 'precio' => 350.00],
            ['nombre' => 'Manicure', 'duracion_minutos' => 45, 'precio' => 200.00],
        ];

        foreach ($servicios as $servicio) {
            Service::firstOrCreate(
                [
                    'business_id' => $business->id,
                    'nombre' => $servicio['nombre']
                ],
                [
                    'descripcion' => 'Servicio de ' . $servicio['nombre'],
                    'duracion_minutos' => $servicio['duracion_minutos'],
                    'precio' => $servicio['precio'],
                ]
            );
        }

        // Crear empleados
        $empleados = [
            ['nombre' => 'Juan Pérez', 'email' => 'juan@test.com'],
            ['nombre' => 'María García', 'email' => 'maria@test.com'],
        ];

        foreach ($empleados as $emp) {
            Employee::firstOrCreate(
                [
                    'business_id' => $business->id,
                    'email' => $emp['email']
                ],
                [
                    'nombre' => $emp['nombre'],
                    'telefono' => '+525512345678',
                ]
            );
        }

        // Crear horarios
        $horarios = [
            ['dia_semana' => 1, 'hora_apertura' => '09:00', 'hora_cierre' => '18:00'], // Lunes
            ['dia_semana' => 2, 'hora_apertura' => '09:00', 'hora_cierre' => '18:00'], // Martes
            ['dia_semana' => 3, 'hora_apertura' => '09:00', 'hora_cierre' => '18:00'], // Miércoles
            ['dia_semana' => 4, 'hora_apertura' => '09:00', 'hora_cierre' => '18:00'], // Jueves
            ['dia_semana' => 5, 'hora_apertura' => '09:00', 'hora_cierre' => '18:00'], // Viernes
            ['dia_semana' => 6, 'hora_apertura' => '09:00', 'hora_cierre' => '14:00'], // Sábado
        ];

        foreach ($horarios as $horario) {
            ScheduleTemplate::firstOrCreate(
                [
                    'business_location_id' => $location->id,
                    'dia_semana' => $horario['dia_semana']
                ],
                [
                    'hora_apertura' => $horario['hora_apertura'],
                    'hora_cierre' => $horario['hora_cierre'],
                ]
            );
        }

        $this->command->info('✅ Usuario test creado: test@citasempresariales.com / password');
        $this->command->info('✅ Negocio, sucursal, servicios, empleados y horarios creados');
    }
}
