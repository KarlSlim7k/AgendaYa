<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\Employee;
use App\Models\ScheduleTemplate;
use App\Models\Service;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    private AvailabilityService $availabilityService;
    private Business $business;
    private BusinessLocation $location;
    private Service $service;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->availabilityService = new AvailabilityService();

        // Crear datos de prueba
        $this->business = Business::factory()->create(['estado' => 'approved']);
        
        $this->location = BusinessLocation::factory()->create([
            'business_id' => $this->business->id,
            'zona_horaria' => 'America/Mexico_City',
            'activo' => true,
        ]);

        $this->service = Service::create([
            'business_id' => $this->business->id,
            'nombre' => 'Corte de cabello',
            'descripcion' => 'Servicio de prueba',
            'precio' => 150.00,
            'duracion_minutos' => 30,
            'buffer_pre_minutos' => 0,
            'buffer_post_minutos' => 10,
            'activo' => true,
        ]);

        $this->employee = Employee::create([
            'business_id' => $this->business->id,
            'nombre' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'estado' => 'disponible',
        ]);

        // Asignar servicio a empleado
        $this->employee->services()->attach($this->service->id);

        // Crear horario base: Lun-Vie 9:00-18:00
        for ($dia = 1; $dia <= 5; $dia++) {
            ScheduleTemplate::create([
                'business_location_id' => $this->location->id,
                'dia_semana' => $dia,
                'hora_apertura' => '09:00',
                'hora_cierre' => '18:00',
                'activo' => true,
            ]);
        }
    }

    /** @test */
    public function puede_generar_slots_basicos_sin_conflictos()
    {
        // Lunes próximo
        $fecha = Carbon::now()->next(Carbon::MONDAY);

        $slots = $this->availabilityService->generateSlots(
            $this->business->id,
            $this->service->id,
            $this->location->id,
            $fecha,
            $fecha
        );

        // Debe haber slots disponibles
        $this->assertGreaterThan(0, $slots->count());

        // Primer slot debe ser 09:00
        $firstSlot = $slots->first();
        $this->assertEquals('09:00', $firstSlot['fecha_hora_inicio']->format('H:i'));

        // Último slot debe terminar antes o a las 18:00
        $lastSlot = $slots->last();
        $this->assertLessThanOrEqual('18:00', $lastSlot['fecha_hora_fin']->format('H:i'));
    }

    /** @test */
    public function no_genera_slots_para_dia_sin_horario()
    {
        // Domingo (no tiene horario configurado)
        $fecha = Carbon::now()->next(Carbon::SUNDAY);

        $slots = $this->availabilityService->generateSlots(
            $this->business->id,
            $this->service->id,
            $this->location->id,
            $fecha,
            $fecha
        );

        $this->assertCount(0, $slots);
    }

    /** @test */
    public function slots_respetan_duracion_del_servicio()
    {
        $fecha = Carbon::now()->next(Carbon::MONDAY);

        $slots = $this->availabilityService->generateSlots(
            $this->business->id,
            $this->service->id,
            $this->location->id,
            $fecha,
            $fecha
        );

        // Cada slot debe tener exactamente 30 minutos
        foreach ($slots as $slot) {
            $duracion = $slot['fecha_hora_inicio']->diffInMinutes($slot['fecha_hora_fin']);
            $this->assertEquals(30, $duracion);
        }
    }

    /** @test */
    public function ultimo_slot_no_excede_horario_cierre()
    {
        // Servicio de 60 minutos
        $servicioLargo = Service::create([
            'business_id' => $this->business->id,
            'nombre' => 'Servicio largo',
            'precio' => 300.00,
            'duracion_minutos' => 60,
            'buffer_pre_minutos' => 0,
            'buffer_post_minutos' => 0,
            'activo' => true,
        ]);

        $this->employee->services()->attach($servicioLargo->id);

        $fecha = Carbon::now()->next(Carbon::MONDAY);

        $slots = $this->availabilityService->generateSlots(
            $this->business->id,
            $servicioLargo->id,
            $this->location->id,
            $fecha,
            $fecha
        );

        // El último slot debe iniciar a las 17:00 para terminar a las 18:00
        $lastSlot = $slots->last();
        $this->assertEquals('17:00', $lastSlot['fecha_hora_inicio']->format('H:i'));
        $this->assertEquals('18:00', $lastSlot['fecha_hora_fin']->format('H:i'));
    }

    /** @test */
    public function puede_filtrar_por_empleado_especifico()
    {
        // Crear otro empleado
        $otroEmpleado = Employee::create([
            'business_id' => $this->business->id,
            'nombre' => 'María López',
            'email' => 'maria@example.com',
            'estado' => 'disponible',
        ]);
        $otroEmpleado->services()->attach($this->service->id);

        $fecha = Carbon::now()->next(Carbon::MONDAY);

        // Filtrar solo por primer empleado
        $slots = $this->availabilityService->generateSlots(
            $this->business->id,
            $this->service->id,
            $this->location->id,
            $fecha,
            $fecha,
            $this->employee->id
        );

        // Todos los slots deben ser del empleado filtrado
        foreach ($slots as $slot) {
            $this->assertEquals($this->employee->id, $slot['employee_id']);
        }
    }

    /** @test */
    public function no_genera_slots_para_empleado_no_disponible()
    {
        // Cambiar estado del empleado
        $this->employee->update(['estado' => 'vacaciones']);

        $fecha = Carbon::now()->next(Carbon::MONDAY);

        $slots = $this->availabilityService->generateSlots(
            $this->business->id,
            $this->service->id,
            $this->location->id,
            $fecha,
            $fecha
        );

        $this->assertCount(0, $slots);
    }

    /** @test */
    public function valida_slot_disponible_correctamente()
    {
        $fecha = Carbon::now()->next(Carbon::MONDAY)->setTime(10, 0);

        $isAvailable = $this->availabilityService->validateSlot(
            $this->business->id,
            $this->service->id,
            $this->employee->id,
            $fecha
        );

        $this->assertTrue($isAvailable);
    }

    /** @test */
    public function rechaza_fecha_fin_anterior_a_fecha_inicio()
    {
        $this->expectException(\InvalidArgumentException::class);

        $fechaInicio = Carbon::now()->addDays(2);
        $fechaFin = Carbon::now()->addDays(1);

        $this->availabilityService->generateSlots(
            $this->business->id,
            $this->service->id,
            $this->location->id,
            $fechaInicio,
            $fechaFin
        );
    }
}
