<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Service;
use App\Models\Business;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmployeeServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $businesses = Business::all();

        foreach ($businesses as $business) {
            $employees = Employee::where('business_id', $business->id)->get();
            $services = Service::where('business_id', $business->id)->get();

            if ($employees->isEmpty() || $services->isEmpty()) {
                continue;
            }

            // Asignar servicios a empleados
            foreach ($employees as $index => $employee) {
                // Primer empleado: todos los servicios
                if ($index === 0) {
                    foreach ($services as $service) {
                        DB::table('employee_services')->insert([
                            'employee_id' => $employee->id,
                            'service_id' => $service->id,
                            'created_at' => now(),
                        ]);
                    }
                } else {
                    // Otros empleados: 1-2 servicios aleatorios
                    $serviciosAsignados = $services->random(min(2, $services->count()));
                    foreach ($serviciosAsignados as $service) {
                        DB::table('employee_services')->insert([
                            'employee_id' => $employee->id,
                            'service_id' => $service->id,
                            'created_at' => now(),
                        ]);
                    }
                }
            }
        }

        $this->command->info('✅ Servicios asignados a empleados');
    }
}
