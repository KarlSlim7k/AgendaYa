<?php

namespace Database\Seeders;

use App\Models\Business;
use Illuminate\Database\Seeder;

class BusinessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear 5 negocios específicos y variados
        $businesses = [
            [
                'nombre' => 'Estilos Modernos',
                'razon_social' => 'Estilos Modernos S.A. de C.V.',
                'rfc' => 'EMO010101ABC',
                'telefono' => '+525512345678',
                'email' => 'contacto@estilosmodernos.com',
                'categoria' => 'peluqueria',
                'descripcion' => 'Peluquería moderna con servicios de corte, peinado y coloración',
                'estado' => 'approved',
                'meta' => [
                    'horario_general' => '09:00-19:00',
                    'dias_laborales' => ['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'],
                    'requiere_deposito' => false,
                ],
            ],
            [
                'nombre' => 'Clínica San Rafael',
                'razon_social' => 'Clínica San Rafael S.C.',
                'rfc' => 'CSR020202DEF',
                'telefono' => '+525587654321',
                'email' => 'citas@clinicasanrafael.com',
                'categoria' => 'clinica',
                'descripcion' => 'Clínica médica familiar con múltiples especialidades',
                'estado' => 'approved',
                'meta' => [
                    'horario_general' => '08:00-20:00',
                    'dias_laborales' => ['lunes', 'martes', 'miércoles', 'jueves', 'viernes'],
                    'requiere_deposito' => true,
                ],
            ],
            [
                'nombre' => 'Taller Mecánico Rodríguez',
                'razon_social' => 'Taller Mecánico Rodríguez S.A. de C.V.',
                'rfc' => 'TMR030303GHI',
                'telefono' => '+525598765432',
                'email' => 'servicio@tallerrodriguez.com',
                'categoria' => 'taller_mecanico',
                'descripcion' => 'Taller mecánico automotriz con servicio express',
                'estado' => 'approved',
                'meta' => [
                    'horario_general' => '08:00-18:00',
                    'dias_laborales' => ['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'],
                    'requiere_deposito' => false,
                ],
            ],
            [
                'nombre' => 'Spa Relax Total',
                'razon_social' => 'Spa Relax Total S.A. de C.V.',
                'rfc' => 'SRT040404JKL',
                'telefono' => '+525523456789',
                'email' => 'reservas@sparelax.com',
                'categoria' => 'spa',
                'descripcion' => 'Spa de lujo con tratamientos de relajación y belleza',
                'estado' => 'approved',
                'meta' => [
                    'horario_general' => '10:00-21:00',
                    'dias_laborales' => ['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'],
                    'requiere_deposito' => true,
                ],
            ],
            [
                'nombre' => 'Consultorio Dr. Pérez',
                'razon_social' => 'Consultorio Dr. Pérez S.C.',
                'rfc' => 'CDP050505MNO',
                'telefono' => '+525534567890',
                'email' => 'citas@drperez.com',
                'categoria' => 'consultorio',
                'descripcion' => 'Consultorio médico general y medicina preventiva',
                'estado' => 'pending', // Este aún pendiente de aprobación
                'meta' => [
                    'horario_general' => '09:00-17:00',
                    'dias_laborales' => ['lunes', 'martes', 'jueves', 'viernes'],
                    'requiere_deposito' => false,
                ],
            ],
        ];

        foreach ($businesses as $businessData) {
            Business::create($businessData);
        }

        $this->command->info('✅ 5 negocios creados (4 aprobados, 1 pendiente)');
    }
}
