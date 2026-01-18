<?php

namespace Tests\Feature\Api;

use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\Employee;
use App\Models\ScheduleTemplate;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_available_slots_for_public_endpoint()
    {
        // Setup: Crear negocio, sucursal, servicio, empleado, horarios
        $business = Business::factory()->create(['estado' => 'approved']);
        $location = BusinessLocation::factory()->create([
            'business_id' => $business->id,
        ]);

        $service = Service::factory()->create([
            'business_id' => $business->id,
            'duracion_minutos' => 30,
        ]);

        $employee = Employee::factory()->create([
            'business_id' => $business->id,
            'estado' => 'disponible',
        ]);

        $employee->servicios()->attach($service->id);

        // Crear horario: Lunes 9:00-18:00
        ScheduleTemplate::create([
            'business_location_id' => $location->id,
            'dia_semana' => 1, // Lunes
            'hora_apertura' => '09:00',
            'hora_cierre' => '18:00',
            'activo' => true,
        ]);

        // Obtener próximo lunes
        $nextMonday = Carbon::parse('next monday')->format('Y-m-d');

        // Llamar endpoint público (sin autenticación)
        $response = $this->getJson('/api/v1/availability/slots?' . http_build_query([
            'business_id' => $business->id,
            'service_id' => $service->id,
            'location_id' => $location->id,
            'fecha_inicio' => $nextMonday,
            'fecha_fin' => $nextMonday,
        ]));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'fecha_hora_inicio',
                        'fecha_hora_fin',
                        'employee_id',
                        'employee_nombre',
                        'service_id',
                    ],
                ],
                'meta' => [
                    'total_slots',
                    'dias_consultados',
                ],
            ]);

        // Verificar que hay slots disponibles
        $data = $response->json('data');
        $this->assertGreaterThan(0, count($data));

        // Verificar estructura de primer slot
        $firstSlot = $data[0];
        $this->assertArrayHasKey('fecha_hora_inicio', $firstSlot);
        $this->assertArrayHasKey('fecha_hora_fin', $firstSlot);
        $this->assertArrayHasKey('employee_id', $firstSlot);
        $this->assertArrayHasKey('employee_nombre', $firstSlot);
    }

    /** @test */
    public function it_validates_required_parameters()
    {
        $response = $this->getJson('/api/v1/availability/slots');

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'business_id',
                'service_id',
                'location_id',
                'fecha_inicio',
            ]);
    }

    /** @test */
    public function it_validates_fecha_fin_after_fecha_inicio()
    {
        $business = Business::factory()->create();
        $location = BusinessLocation::factory()->create([
            'business_id' => $business->id,
        ]);
        $service = Service::factory()->create([
            'business_id' => $business->id,
        ]);

        $response = $this->getJson('/api/v1/availability/slots?' . http_build_query([
            'business_id' => $business->id,
            'service_id' => $service->id,
            'location_id' => $location->id,
            'fecha_inicio' => '2026-02-01',
            'fecha_fin' => '2026-01-15', // Antes de fecha_inicio
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors('fecha_fin');
    }

    /** @test */
    public function it_limits_date_range_to_30_days()
    {
        $business = Business::factory()->create();
        $location = BusinessLocation::factory()->create([
            'business_id' => $business->id,
        ]);
        $service = Service::factory()->create([
            'business_id' => $business->id,
        ]);

        $response = $this->getJson('/api/v1/availability/slots?' . http_build_query([
            'business_id' => $business->id,
            'service_id' => $service->id,
            'location_id' => $location->id,
            'fecha_inicio' => now()->format('Y-m-d'),
            'fecha_fin' => now()->addDays(35)->format('Y-m-d'), // Más de 30 días
        ]));

        $response->assertStatus(422)
            ->assertJsonFragment(['code' => 'DATE_RANGE_EXCEEDED']);
    }

    /** @test */
    public function it_filters_slots_by_specific_employee()
    {
        $business = Business::factory()->create(['estado' => 'approved']);
        $location = BusinessLocation::factory()->create([
            'business_id' => $business->id,
        ]);

        $service = Service::factory()->create([
            'business_id' => $business->id,
            'duracion_minutos' => 30,
        ]);

        $employee = Employee::factory()->create([
            'business_id' => $business->id,
            'estado' => 'disponible',
        ]);

        $employee->servicios()->attach($service->id);

        ScheduleTemplate::create([
            'business_location_id' => $location->id,
            'dia_semana' => 1,
            'hora_apertura' => '09:00',
            'hora_cierre' => '18:00',
            'activo' => true,
        ]);

        $nextMonday = Carbon::parse('next monday')->format('Y-m-d');

        $response = $this->getJson('/api/v1/availability/slots?' . http_build_query([
            'business_id' => $business->id,
            'service_id' => $service->id,
            'location_id' => $location->id,
            'fecha_inicio' => $nextMonday,
            'fecha_fin' => $nextMonday,
            'employee_id' => $employee->id,
        ]));

        $response->assertOk();

        $data = $response->json('data');

        // Verificar que todos los slots tienen el empleado filtrado
        foreach ($data as $slot) {
            $this->assertEquals($employee->id, $slot['employee_id']);
        }
    }
}
