<?php

namespace Tests\Feature\Api\E2E;

use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\Service;
use App\Models\Employee;
use App\Models\ScheduleTemplate;
use App\Models\Appointment;
use App\Models\Role;
use App\Models\BusinessUserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Test E2E del flujo completo de negocio
 * 
 * Flujo probado:
 * 1. Crear negocio
 * 2. Crear sucursal
 * 3. Crear servicios
 * 4. Crear empleados
 * 5. Configurar horarios
 * 6. Generar slots disponibles
 * 7. Usuario crea cita
 * 8. Verificar estados de cita
 */
class CompleteBusinessFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Business $business;
    protected BusinessLocation $location;
    protected Service $service;
    protected Employee $employee;
    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear usuario propietario del negocio
        $this->owner = User::factory()->create([
            'nombre' => 'Business Owner',
            'email' => 'owner@example.com',
        ]);

        // Crear rol de NEGOCIO_ADMIN si no existe
        $adminRole = Role::firstOrCreate(
            ['nombre' => 'NEGOCIO_ADMIN'],
            [
                'display_name' => 'Administrador de Negocio',
                'descripcion' => 'Administrador del negocio',
                'nivel_jerarquia' => 3,
            ]
        );
        
        // Crear negocio
        $this->business = Business::factory()->create([
            'nombre' => 'Test Business E2E',
            'estado' => 'approved',
        ]);

        BusinessUserRole::create([
            'user_id' => $this->owner->id,
            'business_id' => $this->business->id,
            'role_id' => $adminRole->id,
        ]);

        $this->owner->update(['current_business_id' => $this->business->id]);

        // Crear cliente
        $this->customer = User::factory()->create([
            'nombre' => 'Customer User',
            'email' => 'customer@example.com',
        ]);
    }

    /** @test */
    public function flujo_completo_desde_creacion_de_negocio_hasta_cita_exitosa()
    {
        // Setup: Crear toda la estructura con factories
        $this->actingAs($this->owner, 'sanctum');

        // Paso 1: Crear sucursal
        $this->location = BusinessLocation::factory()->create([
            'business_id' => $this->business->id,
            'nombre' => 'Sucursal Principal',
        ]);
        $this->assertNotNull($this->location);

        // Paso 2: Crear servicio via API
        $serviceData = [
            'nombre' => 'Corte de cabello',
            'descripcion' => 'Corte moderno',
            'duracion_minutos' => 30,
            'precio' => 150.00,
            'activo' => true,
        ];

        $response = $this->postJson('/api/v1/services', $serviceData);
        $response->assertStatus(201);

        $this->service = Service::find($response->json('data.id'));
        $this->assertNotNull($this->service);

        // Paso 3: Crear empleado via API
        $employeeData = [
            'nombre' => 'Juan Pérez',
            'email' => 'juan@business.com',
            'telefono' => '+52 55 1234 5678',
            'estado' => 'disponible',
            'service_ids' => [$this->service->id],
        ];

        $response = $this->postJson('/api/v1/employees', $employeeData);
        $response->assertStatus(201);

        $this->employee = Employee::find($response->json('data.id'));
        $this->assertNotNull($this->employee);

        // Paso 4: Configurar horarios - para el próximo día laborable
        $targetDate = now()->addDay();
        $dayOfWeek = $targetDate->dayOfWeek; // 0=Dom, 1=Lun, etc.
        
        $scheduleData = [
            'dia_semana' => $dayOfWeek,
            'hora_apertura' => '09:00',
            'hora_cierre' => '18:00',
            'activo' => true,
        ];

        $response = $this->postJson(
            "/api/v1/locations/{$this->location->id}/schedules",
            $scheduleData
        );
        $response->assertStatus(201);

        // Paso 5: Generar slots disponibles (endpoint público)
        $fecha = $targetDate->format('Y-m-d');

        $response = $this->getJson("/api/v1/availability/slots?" . http_build_query([
            'business_id' => $this->business->id,
            'service_id' => $this->service->id,
            'location_id' => $this->location->id,
            'fecha_inicio' => $fecha,
            'fecha_fin' => $fecha,
        ]));

        $response->assertStatus(200);
        $slots = $response->json('data');
        $this->assertNotEmpty($slots);

        // Paso 6: Cliente crea cita
        $this->actingAs($this->customer, 'sanctum');

        $firstSlot = $slots[0];
        $appointmentData = [
            'business_id' => $this->business->id,
            'service_id' => $this->service->id,
            'employee_id' => $firstSlot['employee_id'],
            'fecha_hora_inicio' => $firstSlot['fecha_hora_inicio'],
            'notas_cliente' => 'Primera cita de prueba E2E',
        ];

        $response = $this->postJson('/api/v1/appointments', $appointmentData);
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'user_id',
                'business_id',
                'service_id',
                'employee_id',
                'fecha_hora_inicio',
                'fecha_hora_fin',
                'estado',
            ]
        ]);

        $appointment = Appointment::find($response->json('data.id'));
        $this->assertNotNull($appointment);
        // Estado inicial depende de service->requiere_confirmacion
        $this->assertContains($appointment->estado, ['pending', 'confirmed']);
        $this->assertEquals($this->customer->id, $appointment->user_id);

        // Paso 7: Verificar que la cita está en lista de cliente
        $response = $this->getJson('/api/v1/appointments');
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');

        // Paso 8: Si la cita está pending, owner puede confirmarla
        // Si ya está confirmed (porque el servicio no requiere confirmación), verificamos que esté confirmed
        $this->actingAs($this->owner, 'sanctum');
        $appointment->refresh();

        if ($appointment->estado === 'pending') {
            $response = $this->patchJson(
                "/api/v1/appointments/{$appointment->id}",
                ['estado' => 'confirmed']
            );
            $response->assertStatus(200);
            $appointment->refresh();
        }
        
        $this->assertEquals('confirmed', $appointment->estado);
    }

    /** @test */
    public function flujo_completo_con_cancelacion_de_cita()
    {
        // Setup: Crear toda la estructura
        $this->actingAs($this->owner, 'sanctum');

        $location = BusinessLocation::factory()->create([
            'business_id' => $this->business->id,
        ]);

        $service = Service::factory()->create([
            'business_id' => $this->business->id,
            'duracion_minutos' => 30,
        ]);

        $employee = Employee::factory()->create([
            'business_id' => $this->business->id,
        ]);

        $employee->services()->attach($service->id);

        ScheduleTemplate::factory()->create([
            'business_location_id' => $location->id,
            'dia_semana' => now()->addDay()->dayOfWeek,
            'hora_apertura' => '09:00',
            'hora_cierre' => '18:00',
            'activo' => true,
        ]);

        // Cliente crea cita
        $this->actingAs($this->customer, 'sanctum');

        $appointment = Appointment::factory()->create([
            'user_id' => $this->customer->id,
            'business_id' => $this->business->id,
            'service_id' => $service->id,
            'employee_id' => $employee->id,
            'fecha_hora_inicio' => now()->addDay()->setTime(10, 0),
            'fecha_hora_fin' => now()->addDay()->setTime(10, 30),
            'estado' => 'confirmed',
        ]);

        // Cliente cancela cita
        $response = $this->patchJson(
            "/api/v1/appointments/{$appointment->id}/cancel",
            ['motivo_cancelacion' => 'No puedo asistir']
        );

        $response->assertStatus(200);

        $appointment->refresh();
        $this->assertEquals('cancelled', $appointment->estado);
        $this->assertNotNull($appointment->cancelada_en);
        $this->assertEquals('No puedo asistir', $appointment->motivo_cancelacion);
    }

    /** @test */
    public function flujo_completo_con_validacion_de_slots_ocupados()
    {
        // Setup
        $this->actingAs($this->owner, 'sanctum');

        $location = BusinessLocation::factory()->create([
            'business_id' => $this->business->id,
        ]);

        $service = Service::factory()->create([
            'business_id' => $this->business->id,
            'duracion_minutos' => 30,
        ]);

        $employee = Employee::factory()->create([
            'business_id' => $this->business->id,
        ]);

        $employee->services()->attach($service->id);

        ScheduleTemplate::factory()->create([
            'business_location_id' => $location->id,
            'dia_semana' => now()->addDay()->dayOfWeek,
            'hora_apertura' => '09:00',
            'hora_cierre' => '18:00',
            'activo' => true,
        ]);

        // Cliente 1 crea cita
        $this->actingAs($this->customer, 'sanctum');

        $slotTime = now()->addDay()->setTime(10, 0);
        
        $appointment1 = Appointment::factory()->create([
            'user_id' => $this->customer->id,
            'business_id' => $this->business->id,
            'service_id' => $service->id,
            'employee_id' => $employee->id,
            'fecha_hora_inicio' => $slotTime,
            'fecha_hora_fin' => $slotTime->copy()->addMinutes(30),
            'estado' => 'confirmed',
        ]);

        // Cliente 2 intenta reservar mismo slot
        $customer2 = User::factory()->create();
        $this->actingAs($customer2, 'sanctum');

        $response = $this->postJson('/api/v1/appointments', [
            'business_id' => $this->business->id,
            'service_id' => $service->id,
            'employee_id' => $employee->id,
            'fecha_hora_inicio' => $slotTime->format('Y-m-d H:i:s'),
        ]);

        // Debe fallar porque slot está ocupado
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['fecha_hora_inicio']);
    }

    /** @test */
    public function flujo_completo_con_multiple_servicios_y_empleados()
    {
        // Setup
        $this->actingAs($this->owner, 'sanctum');

        $location = BusinessLocation::factory()->create([
            'business_id' => $this->business->id,
        ]);

        // Crear 3 servicios diferentes
        $service1 = Service::factory()->create([
            'business_id' => $this->business->id,
            'nombre' => 'Corte básico',
            'duracion_minutos' => 30,
            'precio' => 100,
        ]);

        $service2 = Service::factory()->create([
            'business_id' => $this->business->id,
            'nombre' => 'Corte + barba',
            'duracion_minutos' => 45,
            'precio' => 150,
        ]);

        $service3 = Service::factory()->create([
            'business_id' => $this->business->id,
            'nombre' => 'Tinte completo',
            'duracion_minutos' => 90,
            'precio' => 300,
        ]);

        // Crear 2 empleados con diferentes servicios
        $employee1 = Employee::factory()->create([
            'business_id' => $this->business->id,
            'nombre' => 'Empleado 1',
        ]);
        $employee1->services()->attach([$service1->id, $service2->id]);

        $employee2 = Employee::factory()->create([
            'business_id' => $this->business->id,
            'nombre' => 'Empleado 2',
        ]);
        $employee2->services()->attach([$service2->id, $service3->id]);

        ScheduleTemplate::factory()->create([
            'business_location_id' => $location->id,
            'dia_semana' => now()->addDay()->dayOfWeek,
            'hora_apertura' => '09:00',
            'hora_cierre' => '18:00',
            'activo' => true,
        ]);

        // Cliente reserva servicio 1 con empleado 1
        $this->actingAs($this->customer, 'sanctum');

        $appointment1 = Appointment::factory()->create([
            'user_id' => $this->customer->id,
            'business_id' => $this->business->id,
            'service_id' => $service1->id,
            'employee_id' => $employee1->id,
            'fecha_hora_inicio' => now()->addDay()->setTime(10, 0),
            'fecha_hora_fin' => now()->addDay()->setTime(10, 30),
            'estado' => 'confirmed',
        ]);

        // Mismo cliente reserva servicio 3 con empleado 2 (distinto horario)
        $appointment2 = Appointment::factory()->create([
            'user_id' => $this->customer->id,
            'business_id' => $this->business->id,
            'service_id' => $service3->id,
            'employee_id' => $employee2->id,
            'fecha_hora_inicio' => now()->addDay()->setTime(14, 0),
            'fecha_hora_fin' => now()->addDay()->setTime(15, 30),
            'estado' => 'confirmed',
        ]);

        // Verificar que ambas citas existen
        $this->assertDatabaseCount('appointments', 2);
        
        $response = $this->getJson('/api/v1/appointments');
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }
}
