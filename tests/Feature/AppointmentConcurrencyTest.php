<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\Employee;
use App\Models\ScheduleTemplate;
use App\Models\Service;
use App\Models\User;
use App\Services\AppointmentService;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tests de concurrencia para prevención de doble booking
 */
class AppointmentConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    private AppointmentService $appointmentService;
    private Business $business;
    private BusinessLocation $location;
    private Service $service;
    private Employee $employee;
    private User $user1;
    private User $user2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->appointmentService = app(AppointmentService::class);

        // Crear datos de prueba
        $this->business = Business::factory()->create(['estado' => 'approved']);
        
        $this->location = BusinessLocation::factory()->create([
            'business_id' => $this->business->id,
            'zona_horaria' => 'America/Mexico_City',
            'activo' => true,
        ]);

        $this->service = Service::factory()->create([
            'business_id' => $this->business->id,
            'duracion_minutos' => 30,
            'buffer_pre_minutos' => 0,
            'buffer_post_minutos' => 10,
            'requiere_confirmacion' => false,
            'activo' => true,
        ]);

        $this->employee = Employee::factory()->create([
            'business_id' => $this->business->id,
            'estado' => 'disponible',
        ]);

        // Asociar empleado con servicio
        $this->employee->services()->attach($this->service->id);

        // Crear horarios de la sucursal (Lun-Vie 09:00-18:00)
        for ($dia = 1; $dia <= 5; $dia++) {
            ScheduleTemplate::factory()->create([
                'business_location_id' => $this->location->id,
                'dia_semana' => $dia,
                'hora_apertura' => '09:00',
                'hora_cierre' => '18:00',
                'activo' => true,
            ]);
        }

        // Crear usuarios de prueba
        $this->user1 = User::factory()->create();
        $this->user2 = User::factory()->create();
    }

    /**
     * Test: No permite crear dos citas en el mismo horario para el mismo empleado
     */
    public function test_prevents_double_booking_same_slot(): void
    {
        // Encontrar próximo día laborable
        $fecha = Carbon::now()->next(Carbon::MONDAY)->setHour(10)->setMinute(0)->setSecond(0);

        // Primera cita debe crearse exitosamente
        $appointment1 = $this->appointmentService->createAppointment([
            'business_id' => $this->business->id,
            'user_id' => $this->user1->id,
            'employee_id' => $this->employee->id,
            'service_id' => $this->service->id,
            'fecha_hora_inicio' => $fecha,
        ]);

        $this->assertNotNull($appointment1);
        $this->assertEquals(Appointment::ESTADO_CONFIRMED, $appointment1->estado);

        // Segunda cita en el mismo horario debe fallar
        $this->expectException(\App\Exceptions\SlotNotAvailableException::class);

        $this->appointmentService->createAppointment([
            'business_id' => $this->business->id,
            'user_id' => $this->user2->id,
            'employee_id' => $this->employee->id,
            'service_id' => $this->service->id,
            'fecha_hora_inicio' => $fecha,
        ]);
    }

    /**
     * Test: Respeta buffer post-cita (no permite cita que inicie durante el buffer)
     */
    public function test_respects_buffer_post_appointment(): void
    {
        $fecha = Carbon::now()->next(Carbon::MONDAY)->setHour(10)->setMinute(0)->setSecond(0);

        // Primera cita 10:00-10:30 + 10 min buffer = ocupa hasta 10:40
        $appointment1 = $this->appointmentService->createAppointment([
            'business_id' => $this->business->id,
            'user_id' => $this->user1->id,
            'employee_id' => $this->employee->id,
            'service_id' => $this->service->id,
            'fecha_hora_inicio' => $fecha,
        ]);

        $this->assertNotNull($appointment1);

        // Intentar cita a las 10:35 (dentro del buffer) debe fallar
        $this->expectException(\App\Exceptions\SlotNotAvailableException::class);

        $this->appointmentService->createAppointment([
            'business_id' => $this->business->id,
            'user_id' => $this->user2->id,
            'employee_id' => $this->employee->id,
            'service_id' => $this->service->id,
            'fecha_hora_inicio' => $fecha->copy()->addMinutes(35),
        ]);
    }

    /**
     * Test: Permite citas después del buffer
     */
    public function test_allows_appointment_after_buffer(): void
    {
        $fecha = Carbon::now()->next(Carbon::MONDAY)->setHour(10)->setMinute(0)->setSecond(0);

        // Primera cita 10:00-10:30 + 10 min buffer = ocupa hasta 10:40
        $appointment1 = $this->appointmentService->createAppointment([
            'business_id' => $this->business->id,
            'user_id' => $this->user1->id,
            'employee_id' => $this->employee->id,
            'service_id' => $this->service->id,
            'fecha_hora_inicio' => $fecha,
        ]);

        // Cita a las 10:45 (después del buffer) debe permitirse
        $appointment2 = $this->appointmentService->createAppointment([
            'business_id' => $this->business->id,
            'user_id' => $this->user2->id,
            'employee_id' => $this->employee->id,
            'service_id' => $this->service->id,
            'fecha_hora_inicio' => $fecha->copy()->addMinutes(45),
        ]);

        $this->assertNotNull($appointment2);
        $this->assertNotEquals($appointment1->id, $appointment2->id);
    }

    /**
     * Test: Citas canceladas no bloquean el slot
     */
    public function test_cancelled_appointments_do_not_block_slot(): void
    {
        $fecha = Carbon::now()->next(Carbon::MONDAY)->setHour(10)->setMinute(0)->setSecond(0);

        // Crear y cancelar primera cita
        $appointment1 = $this->appointmentService->createAppointment([
            'business_id' => $this->business->id,
            'user_id' => $this->user1->id,
            'employee_id' => $this->employee->id,
            'service_id' => $this->service->id,
            'fecha_hora_inicio' => $fecha,
        ]);

        $this->appointmentService->cancelAppointment(
            $appointment1,
            $this->user1->id,
            'Cambio de planes'
        );

        // Segunda cita en el mismo horario debe permitirse
        $appointment2 = $this->appointmentService->createAppointment([
            'business_id' => $this->business->id,
            'user_id' => $this->user2->id,
            'employee_id' => $this->employee->id,
            'service_id' => $this->service->id,
            'fecha_hora_inicio' => $fecha,
        ]);

        $this->assertNotNull($appointment2);
        $this->assertEquals(Appointment::ESTADO_CONFIRMED, $appointment2->estado);
    }

    /**
     * Test: Diferentes empleados pueden tener citas simultáneas
     */
    public function test_different_employees_can_have_simultaneous_appointments(): void
    {
        // Crear segundo empleado
        $employee2 = Employee::factory()->create([
            'business_id' => $this->business->id,
            'estado' => 'disponible',
        ]);
        $employee2->services()->attach($this->service->id);

        $fecha = Carbon::now()->next(Carbon::MONDAY)->setHour(10)->setMinute(0)->setSecond(0);

        // Cita con empleado 1
        $appointment1 = $this->appointmentService->createAppointment([
            'business_id' => $this->business->id,
            'user_id' => $this->user1->id,
            'employee_id' => $this->employee->id,
            'service_id' => $this->service->id,
            'fecha_hora_inicio' => $fecha,
        ]);

        // Cita con empleado 2 en el mismo horario
        $appointment2 = $this->appointmentService->createAppointment([
            'business_id' => $this->business->id,
            'user_id' => $this->user2->id,
            'employee_id' => $employee2->id,
            'service_id' => $this->service->id,
            'fecha_hora_inicio' => $fecha,
        ]);

        $this->assertNotNull($appointment1);
        $this->assertNotNull($appointment2);
        $this->assertEquals($appointment1->fecha_hora_inicio->format('Y-m-d H:i'), 
                           $appointment2->fecha_hora_inicio->format('Y-m-d H:i'));
    }

    /**
     * Test: Citas solapadas parcialmente son rechazadas
     */
    public function test_rejects_partially_overlapping_appointments(): void
    {
        $fecha = Carbon::now()->next(Carbon::MONDAY)->setHour(10)->setMinute(0)->setSecond(0);

        // Primera cita 10:00-10:30
        $appointment1 = $this->appointmentService->createAppointment([
            'business_id' => $this->business->id,
            'user_id' => $this->user1->id,
            'employee_id' => $this->employee->id,
            'service_id' => $this->service->id,
            'fecha_hora_inicio' => $fecha,
        ]);

        // Intentar cita 10:15-10:45 (solapa con la primera)
        $this->expectException(\App\Exceptions\SlotNotAvailableException::class);

        $this->appointmentService->createAppointment([
            'business_id' => $this->business->id,
            'user_id' => $this->user2->id,
            'employee_id' => $this->employee->id,
            'service_id' => $this->service->id,
            'fecha_hora_inicio' => $fecha->copy()->addMinutes(15),
        ]);
    }

    /**
     * Test: Múltiples citas consecutivas se crean correctamente
     */
    public function test_multiple_consecutive_appointments_work(): void
    {
        $fecha = Carbon::now()->next(Carbon::MONDAY)->setHour(9)->setMinute(0)->setSecond(0);
        $appointments = [];

        // Crear 4 citas consecutivas (cada una 30 min + 10 buffer = 40 min intervalo)
        for ($i = 0; $i < 4; $i++) {
            $user = User::factory()->create();
            $horaInicio = $fecha->copy()->addMinutes($i * 45);

            $appointments[] = $this->appointmentService->createAppointment([
                'business_id' => $this->business->id,
                'user_id' => $user->id,
                'employee_id' => $this->employee->id,
                'service_id' => $this->service->id,
                'fecha_hora_inicio' => $horaInicio,
            ]);
        }

        $this->assertCount(4, $appointments);
        foreach ($appointments as $appointment) {
            $this->assertEquals(Appointment::ESTADO_CONFIRMED, $appointment->estado);
        }
    }
}
