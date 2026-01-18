<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\Business;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $businesses = Business::all();

        foreach ($businesses as $business) {
            // Servicios según categoría del negocio
            $servicios = $this->getServiciosPorCategoria($business->categoria);

            foreach ($servicios as $servicioData) {
                Service::create([
                    'business_id' => $business->id,
                    'nombre' => $servicioData['nombre'],
                    'descripcion' => $servicioData['descripcion'],
                    'precio' => $servicioData['precio'],
                    'duracion_minutos' => $servicioData['duracion_minutos'],
                    'buffer_pre_minutos' => $servicioData['buffer_pre_minutos'] ?? 0,
                    'buffer_post_minutos' => $servicioData['buffer_post_minutos'] ?? 0,
                    'requiere_confirmacion' => $servicioData['requiere_confirmacion'] ?? false,
                    'activo' => true,
                    'meta' => $servicioData['meta'] ?? null,
                ]);
            }
        }

        $this->command->info('✅ Servicios creados para todos los negocios');
    }

    /**
     * Obtener servicios según categoría del negocio
     */
    private function getServiciosPorCategoria(?string $categoria): array
    {
        $serviciosPorCategoria = [
            'peluqueria' => [
                [
                    'nombre' => 'Corte de cabello',
                    'descripcion' => 'Corte de cabello clásico o moderno',
                    'precio' => 150.00,
                    'duracion_minutos' => 30,
                    'buffer_pre_minutos' => 0,
                    'buffer_post_minutos' => 10,
                    'requiere_confirmacion' => false,
                ],
                [
                    'nombre' => 'Tinte completo',
                    'descripcion' => 'Tinte de cabello completo con secado',
                    'precio' => 450.00,
                    'duracion_minutos' => 90,
                    'buffer_pre_minutos' => 0,
                    'buffer_post_minutos' => 15,
                    'requiere_confirmacion' => false,
                ],
                [
                    'nombre' => 'Peinado para evento',
                    'descripcion' => 'Peinado profesional para eventos especiales',
                    'precio' => 350.00,
                    'duracion_minutos' => 60,
                    'buffer_pre_minutos' => 5,
                    'buffer_post_minutos' => 10,
                    'requiere_confirmacion' => true,
                ],
            ],
            'clinica' => [
                [
                    'nombre' => 'Consulta general',
                    'descripcion' => 'Consulta médica general',
                    'precio' => 500.00,
                    'duracion_minutos' => 30,
                    'buffer_pre_minutos' => 5,
                    'buffer_post_minutos' => 5,
                    'requiere_confirmacion' => false,
                ],
                [
                    'nombre' => 'Consulta especializada',
                    'descripcion' => 'Consulta con especialista',
                    'precio' => 800.00,
                    'duracion_minutos' => 45,
                    'buffer_pre_minutos' => 10,
                    'buffer_post_minutos' => 10,
                    'requiere_confirmacion' => true,
                ],
            ],
            'taller' => [
                [
                    'nombre' => 'Cambio de aceite',
                    'descripcion' => 'Cambio de aceite y filtro',
                    'precio' => 350.00,
                    'duracion_minutos' => 45,
                    'buffer_pre_minutos' => 0,
                    'buffer_post_minutos' => 15,
                    'requiere_confirmacion' => false,
                ],
                [
                    'nombre' => 'Diagnóstico computarizado',
                    'descripcion' => 'Escaneo y diagnóstico del vehículo',
                    'precio' => 500.00,
                    'duracion_minutos' => 60,
                    'buffer_pre_minutos' => 0,
                    'buffer_post_minutos' => 15,
                    'requiere_confirmacion' => false,
                ],
            ],
        ];

        // Si la categoría no existe, usar servicios genéricos
        return $serviciosPorCategoria[$categoria] ?? [
            [
                'nombre' => 'Servicio básico',
                'descripcion' => 'Servicio básico del negocio',
                'precio' => 200.00,
                'duracion_minutos' => 30,
                'buffer_pre_minutos' => 0,
                'buffer_post_minutos' => 5,
                'requiere_confirmacion' => false,
            ],
            [
                'nombre' => 'Servicio premium',
                'descripcion' => 'Servicio premium con atención personalizada',
                'precio' => 500.00,
                'duracion_minutos' => 60,
                'buffer_pre_minutos' => 5,
                'buffer_post_minutos' => 10,
                'requiere_confirmacion' => true,
            ],
        ];
    }
}
