<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Business;
use App\Models\Service;
use App\Models\Employee;
use App\Models\Appointment;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class AppointmentsSeeder extends Seeder
{
    /**
     * Seed the applications appointments table with test data.
     */
    public function run(): void
    {
        // Obtener datos de prueba
        $user = User::where('email', 'test@example.com')->first();
        $business = Business::where('nombre', 'Peluquería Estilos')->first();

        if (!$user || !$business) {
            echo "⚠️  No se encontraron datos de prueba. Ejecuta TestDataSeeder primero.\n";
            return;
        }

        $services = Service::where('business_id', $business->id)->get();
        $employees = Employee::where('business_id', $business->id)->get();

        if ($services->isEmpty() || $employees->isEmpty()) {
            echo "⚠️  No hay servicios o empleados. Ejecuta TestDataSeeder primero.\n";
            return;
        }

        // Crear citas de prueba para los próximos 7 días
        $now = Carbon::now()->setTimezone('America/Mexico_City');
        
        // Cita 1: Mañana a las 10:00 - Confirmada
        Appointment::firstOrCreate(
            [
                'business_id' => $business->id,
                'user_id' => $user->id,
                'service_id' => $services[0]->id,
                'employee_id' => $employees[0]->id,
                'fecha_hora_inicio' => $now->copy()->addDay()->setHour(10)->setMinute(0)->setSecond(0),
            ],
            [
                'fecha_hora_fin' => $now->copy()->addDay()->setHour(10)->setMinute(30)->setSecond(0),
                'estado' => 'confirmed',
                'custom_data' => json_encode(['tipo_corte' => 'Clásico']),
            ]
        );

        // Cita 2: Pasado mañana a las 14:00 - Pendiente
        Appointment::firstOrCreate(
            [
                'business_id' => $business->id,
                'user_id' => $user->id,
                'service_id' => $services[1]->id,
                'employee_id' => $employees[1]->id,
                'fecha_hora_inicio' => $now->copy()->addDays(2)->setHour(14)->setMinute(0)->setSecond(0),
            ],
            [
                'fecha_hora_fin' => $now->copy()->addDays(2)->setHour(15)->setMinute(0)->setSecond(0),
                'estado' => 'pending',
                'custom_data' => json_encode(['color' => 'Rojo oscuro']),
            ]
        );

        // Cita 3: En 3 días a las 09:00 - Completada
        Appointment::firstOrCreate(
            [
                'business_id' => $business->id,
                'user_id' => $user->id,
                'service_id' => $services[2]->id,
                'employee_id' => $employees[2]->id,
                'fecha_hora_inicio' => $now->copy()->addDays(3)->setHour(9)->setMinute(0)->setSecond(0),
            ],
            [
                'fecha_hora_fin' => $now->copy()->addDays(3)->setHour(9)->setMinute(45)->setSecond(0),
                'estado' => 'completed',
                'custom_data' => json_encode(['estilo_preferido' => 'Ondas']),
            ]
        );

        // Cita 4: En 5 días a las 11:00 - Cancelada
        Appointment::firstOrCreate(
            [
                'business_id' => $business->id,
                'user_id' => $user->id,
                'service_id' => $services[0]->id,
                'employee_id' => $employees[0]->id,
                'fecha_hora_inicio' => $now->copy()->addDays(5)->setHour(11)->setMinute(0)->setSecond(0),
            ],
            [
                'fecha_hora_fin' => $now->copy()->addDays(5)->setHour(11)->setMinute(30)->setSecond(0),
                'estado' => 'cancelled',
                'custom_data' => json_encode(['razon_cancelacion' => 'Emergencia laboral']),
            ]
        );

        // Cita 5: En 6 días a las 15:00 - No-show
        Appointment::firstOrCreate(
            [
                'business_id' => $business->id,
                'user_id' => $user->id,
                'service_id' => $services[1]->id,
                'employee_id' => $employees[1]->id,
                'fecha_hora_inicio' => $now->copy()->addDays(6)->setHour(15)->setMinute(0)->setSecond(0),
            ],
            [
                'fecha_hora_fin' => $now->copy()->addDays(6)->setHour(16)->setMinute(0)->setSecond(0),
                'estado' => 'no_show',
                'custom_data' => json_encode([]),
            ]
        );

        echo "\n✓ Citas de prueba creadas/verificadas exitosamente.\n";
        echo "  📅 Citas totales: 5\n";
        echo "  ✅ Confirmed: 1\n";
        echo "  ⏳ Pending: 1\n";
        echo "  ✔️  Completed: 1\n";
        echo "  ❌ Cancelled: 1\n";
        echo "  🚫 No-Show: 1\n\n";
    }
}
