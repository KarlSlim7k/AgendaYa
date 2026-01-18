<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Business;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $businesses = Business::all();

        foreach ($businesses as $business) {
            // Crear 3 empleados por negocio
            $empleados = $this->getEmpleadosPorCategoria($business->categoria);

            foreach ($empleados as $empleadoData) {
                Employee::create([
                    'business_id' => $business->id,
                    'user_account_id' => null, // Sin cuenta de usuario por ahora
                    'nombre' => $empleadoData['nombre'],
                    'email' => $empleadoData['email'] . '.' . $business->id . '@example.com',
                    'telefono' => '+52 55 ' . rand(1000, 9999) . ' ' . rand(1000, 9999),
                    'avatar_url' => null,
                    'cargo' => $empleadoData['cargo'],
                    'estado' => 'disponible',
                    'meta' => null,
                ]);
            }
        }

        $this->command->info('✅ Empleados creados para todos los negocios');
    }

    /**
     * Obtener empleados según categoría del negocio
     */
    private function getEmpleadosPorCategoria(?string $categoria): array
    {
        $empleadosPorCategoria = [
            'peluqueria' => [
                ['nombre' => 'María González', 'email' => 'maria.gonzalez', 'cargo' => 'Estilista Senior'],
                ['nombre' => 'Carlos Ramírez', 'email' => 'carlos.ramirez', 'cargo' => 'Estilista'],
                ['nombre' => 'Ana López', 'email' => 'ana.lopez', 'cargo' => 'Colorista'],
            ],
            'clinica' => [
                ['nombre' => 'Dr. Jorge Martínez', 'email' => 'jorge.martinez', 'cargo' => 'Médico General'],
                ['nombre' => 'Dra. Laura Hernández', 'email' => 'laura.hernandez', 'cargo' => 'Pediatra'],
                ['nombre' => 'Enfermera Patricia Ruiz', 'email' => 'patricia.ruiz', 'cargo' => 'Enfermera'],
            ],
            'taller' => [
                ['nombre' => 'Juan Pérez', 'email' => 'juan.perez', 'cargo' => 'Mecánico Senior'],
                ['nombre' => 'Roberto García', 'email' => 'roberto.garcia', 'cargo' => 'Mecánico'],
                ['nombre' => 'Miguel Torres', 'email' => 'miguel.torres', 'cargo' => 'Técnico Eléctrico'],
            ],
        ];

        // Empleados genéricos si la categoría no existe
        return $empleadosPorCategoria[$categoria] ?? [
            ['nombre' => 'Pedro Sánchez', 'email' => 'pedro.sanchez', 'cargo' => 'Especialista Senior'],
            ['nombre' => 'Lucía Morales', 'email' => 'lucia.morales', 'cargo' => 'Especialista'],
            ['nombre' => 'Diego Vargas', 'email' => 'diego.vargas', 'cargo' => 'Asistente'],
        ];
    }
}
