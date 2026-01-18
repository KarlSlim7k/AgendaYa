<?php

namespace Database\Seeders;

use App\Models\BusinessLocation;
use App\Models\ScheduleTemplate;
use Illuminate\Database\Seeder;

class ScheduleTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = BusinessLocation::all();

        foreach ($locations as $location) {
            // Horarios base: Lun-Vie 9:00-18:00, Sáb 9:00-14:00, Dom cerrado
            $horarios = [
                // Lunes a Viernes (1-5)
                ['dia_semana' => 1, 'hora_apertura' => '09:00', 'hora_cierre' => '18:00', 'activo' => true],
                ['dia_semana' => 2, 'hora_apertura' => '09:00', 'hora_cierre' => '18:00', 'activo' => true],
                ['dia_semana' => 3, 'hora_apertura' => '09:00', 'hora_cierre' => '18:00', 'activo' => true],
                ['dia_semana' => 4, 'hora_apertura' => '09:00', 'hora_cierre' => '18:00', 'activo' => true],
                ['dia_semana' => 5, 'hora_apertura' => '09:00', 'hora_cierre' => '18:00', 'activo' => true],
                // Sábado (6)
                ['dia_semana' => 6, 'hora_apertura' => '09:00', 'hora_cierre' => '14:00', 'activo' => true],
                // Domingo (0) - Cerrado
                ['dia_semana' => 0, 'hora_apertura' => '09:00', 'hora_cierre' => '18:00', 'activo' => false],
            ];

            foreach ($horarios as $horario) {
                ScheduleTemplate::create([
                    'business_location_id' => $location->id,
                    'dia_semana' => $horario['dia_semana'],
                    'hora_apertura' => $horario['hora_apertura'],
                    'hora_cierre' => $horario['hora_cierre'],
                    'activo' => $horario['activo'],
                ]);
            }
        }

        $this->command->info('✅ Horarios base creados para todas las sucursales');
    }
}
