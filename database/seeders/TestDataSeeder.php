<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\Service;
use App\Models\Employee;
use App\Models\ScheduleTemplate;
use App\Models\BusinessUserRole;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        // Crear usuario de prueba
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'nombre' => 'Usuario',
                'apellidos' => 'Prueba',
                'telefono' => '+5255123456789',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        // Crear negocio de prueba
        $business = Business::firstOrCreate(
            ['nombre' => 'Peluquería Estilos'],
            [
                'razon_social' => 'Peluquería Estilos S.A.',
                'rfc' => 'PES123456ABC',
                'email' => 'peluqueria@test.com',
                'categoria' => 'peluqueria',
                'estado' => 'approved',
                'telefono' => '+5255123456',
            ]
        );

        // Obtener el rol ADMIN del sistema
        $adminRole = Role::where('nombre', 'NEGOCIO_ADMIN')->first();
        
        if ($adminRole) {
            // Asignar usuario al negocio con rol ADMIN (si no existe ya)
            BusinessUserRole::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'business_id' => $business->id,
                    'role_id' => $adminRole->id,
                ]
            );
        }

        // Establecer el negocio actual del usuario
        $user->update(['current_business_id' => $business->id]);

        // Crear sucursal
        $location = BusinessLocation::firstOrCreate(
            [
                'business_id' => $business->id,
                'nombre' => 'Sucursal Centro',
            ],
            [
                'direccion' => 'Calle Principal 123',
                'ciudad' => 'México',
                'estado' => 'CDMX',
                'codigo_postal' => '06500',
                'zona_horaria' => 'America/Mexico_City',
                'activo' => true,
            ]
        );

        // Crear servicios
        $services = [
            [
                'nombre' => 'Corte de Cabello',
                'descripcion' => 'Corte básico de cabello',
                'duracion_minutos' => 30,
                'precio' => 150.00,
                'buffer_pre_minutos' => 5,
                'buffer_post_minutos' => 5,
            ],
            [
                'nombre' => 'Tinte',
                'descripcion' => 'Tinte completo',
                'duracion_minutos' => 60,
                'precio' => 350.00,
                'buffer_pre_minutos' => 10,
                'buffer_post_minutos' => 5,
            ],
            [
                'nombre' => 'Peinado',
                'descripcion' => 'Peinado especial',
                'duracion_minutos' => 45,
                'precio' => 200.00,
                'buffer_pre_minutos' => 5,
                'buffer_post_minutos' => 5,
            ],
        ];

        $serviceIds = [];
        foreach ($services as $serviceData) {
            $service = Service::firstOrCreate(
                [
                    'business_id' => $business->id,
                    'nombre' => $serviceData['nombre'],
                ],
                array_merge($serviceData, ['activo' => true])
            );
            $serviceIds[] = $service->id;
        }

        // Crear empleados
        $employees = [
            [
                'nombre' => 'María García',
                'email' => 'maria@peluqueria.test',
                'telefono' => '+5255111111',
            ],
            [
                'nombre' => 'Carlos López',
                'email' => 'carlos@peluqueria.test',
                'telefono' => '+5255222222',
            ],
            [
                'nombre' => 'Ana Martínez',
                'email' => 'ana@peluqueria.test',
                'telefono' => '+5255333333',
            ],
        ];

        $employeeIds = [];
        foreach ($employees as $employeeData) {
            $employee = Employee::firstOrCreate(
                [
                    'business_id' => $business->id,
                    'email' => $employeeData['email'],
                ],
                array_merge($employeeData, ['estado' => 'disponible'])
            );
            $employeeIds[] = $employee->id;
            
            // Asignar servicios al empleado
            $employee->services()->syncWithoutDetaching($serviceIds);
        }

        // Crear horarios - de Lunes (1) a Sábado (6)
        for ($day = 1; $day <= 6; $day++) {
            ScheduleTemplate::firstOrCreate(
                [
                    'business_location_id' => $location->id,
                    'dia_semana' => $day,
                ],
                [
                    'hora_apertura' => '09:00',
                    'hora_cierre' => '18:00',
                    'activo' => true,
                ]
            );
        }

        echo "\n✓ Datos de prueba creados/verificados exitosamente.\n";
        echo "  📧 Email: test@example.com\n";
        echo "  🔑 Contraseña: password123\n";
        echo "  🏢 Negocio: " . $business->nombre . "\n";
        echo "  📍 Sucursal: " . $location->nombre . "\n";
        echo "  💼 Servicios: " . count($serviceIds) . "\n";
        echo "  👥 Empleados: " . count($employeeIds) . "\n\n";
    }
}
