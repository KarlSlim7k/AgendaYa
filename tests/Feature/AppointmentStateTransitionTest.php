<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Business;
use App\Models\Employee;
use App\Models\Service;
use App\Models\User;
use App\Services\AppointmentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de transiciones de estado de citas
 */
class AppointmentStateTransitionTest extends TestCase
{
    use RefreshDatabase;

    private AppointmentService $appointmentService;
    private Business $business;
    private Service $service;
    private Employee $employee;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->appointmentService = app(AppointmentService::class);

        $this->business = Business::factory()->create(['estado' => 'approved']);
        
        $this->service = Service::factory()->create([
            'business_id' => $this->business->id,
            'duracion_minutos' => 30,
        ]);

        $this->employee = Employee::factory()->create([
            'business_id' => $this->business->id,
        ]);

        $this->user = User::factory()->create();
    }

    /**
     * Test: pending → confirmed es válido
     */
    public function test_pending_to_confirmed_is_valid(): void
    {
        $appointment = Appointment::factory()->create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'employee_id' => $this->employee->id,
            'service_id' => $this->service->id,
            'estado' => Appointment::ESTADO_PENDING,
        ]);

        $result = $appointment->cambiarEstado(Appointment::ESTADO_CONFIRMED);

        $this->assertTrue($result);
        $this->assertEquals(Appointment::ESTADO_CONFIRMED, $appointment->fresh()->estado);
        $this->assertNotNull($appointment->fresh()->confirmada_en);
    }

    /**
     * Test: pending → cancelled es válido
     */
    public function test_pending_to_cancelled_is_valid(): void
    {
        $appointment = Appointment::factory()->create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'employee_id' => $this->employee->id,
            'service_id' => $this->service->id,
            'estado' => Appointment::ESTADO_PENDING,
        ]);

        $result = $appointment->cambiarEstado(Appointment::ESTADO_CANCELLED, [
            'cancelada_por_user_id' => $this->user->id,
            'motivo_cancelacion' => 'Cambio de planes',
        ]);

        $this->assertTrue($result);
        $this->assertEquals(Appointment::ESTADO_CANCELLED, $appointment->fresh()->estado);
        $this->assertNotNull($appointment->fresh()->cancelada_en);
        $this->assertEquals('Cambio de planes', $appointment->fresh()->motivo_cancelacion);
    }

    /**
     * Test: confirmed → completed es válido
     */
    public function test_confirmed_to_completed_is_valid(): void
    {
        $appointment = Appointment::factory()->confirmed()->create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'employee_id' => $this->employee->id,
            'service_id' => $this->service->id,
        ]);

        $result = $appointment->cambiarEstado(Appointment::ESTADO_COMPLETED);

        $this->assertTrue($result);
        $this->assertEquals(Appointment::ESTADO_COMPLETED, $appointment->fresh()->estado);
        $this->assertNotNull($appointment->fresh()->completada_en);
    }

    /**
     * Test: confirmed → cancelled es válido
     */
    public function test_confirmed_to_cancelled_is_valid(): void
    {
        $appointment = Appointment::factory()->confirmed()->create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'employee_id' => $this->employee->id,
            'service_id' => $this->service->id,
        ]);

        $result = $appointment->cambiarEstado(Appointment::ESTADO_CANCELLED);

        $this->assertTrue($result);
        $this->assertEquals(Appointment::ESTADO_CANCELLED, $appointment->fresh()->estado);
    }

    /**
     * Test: confirmed → no_show es válido
     */
    public function test_confirmed_to_no_show_is_valid(): void
    {
        $appointment = Appointment::factory()->confirmed()->create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'employee_id' => $this->employee->id,
            'service_id' => $this->service->id,
        ]);

        $result = $appointment->cambiarEstado(Appointment::ESTADO_NO_SHOW);

        $this->assertTrue($result);
        $this->assertEquals(Appointment::ESTADO_NO_SHOW, $appointment->fresh()->estado);
    }

    /**
     * Test: pending → completed es INVÁLIDO (debe pasar por confirmed)
     */
    public function test_pending_to_completed_is_invalid(): void
    {
        $appointment = Appointment::factory()->create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'employee_id' => $this->employee->id,
            'service_id' => $this->service->id,
            'estado' => Appointment::ESTADO_PENDING,
        ]);

        $this->expectException(\App\Exceptions\InvalidStateTransitionException::class);

        $appointment->cambiarEstado(Appointment::ESTADO_COMPLETED);
    }

    /**
     * Test: pending → no_show es INVÁLIDO
     */
    public function test_pending_to_no_show_is_invalid(): void
    {
        $appointment = Appointment::factory()->create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'employee_id' => $this->employee->id,
            'service_id' => $this->service->id,
            'estado' => Appointment::ESTADO_PENDING,
        ]);

        $this->expectException(\App\Exceptions\InvalidStateTransitionException::class);

        $appointment->cambiarEstado(Appointment::ESTADO_NO_SHOW);
    }

    /**
     * Test: completed → cualquier estado es INVÁLIDO (estado terminal)
     */
    public function test_completed_is_terminal_state(): void
    {
        $appointment = Appointment::factory()->completed()->create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'employee_id' => $this->employee->id,
            'service_id' => $this->service->id,
        ]);

        // No puede ir a cancelled
        $this->expectException(\App\Exceptions\InvalidStateTransitionException::class);
        $appointment->cambiarEstado(Appointment::ESTADO_CANCELLED);
    }

    /**
     * Test: cancelled → cualquier estado es INVÁLIDO (estado terminal)
     */
    public function test_cancelled_is_terminal_state(): void
    {
        $appointment = Appointment::factory()->cancelled()->create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'employee_id' => $this->employee->id,
            'service_id' => $this->service->id,
        ]);

        // No puede ir a confirmed
        $this->expectException(\App\Exceptions\InvalidStateTransitionException::class);
        $appointment->cambiarEstado(Appointment::ESTADO_CONFIRMED);
    }

    /**
     * Test: no_show → cualquier estado es INVÁLIDO (estado terminal)
     */
    public function test_no_show_is_terminal_state(): void
    {
        $appointment = Appointment::factory()->noShow()->create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'employee_id' => $this->employee->id,
            'service_id' => $this->service->id,
        ]);

        // No puede ir a completed
        $this->expectException(\App\Exceptions\InvalidStateTransitionException::class);
        $appointment->cambiarEstado(Appointment::ESTADO_COMPLETED);
    }

    /**
     * Test: Método puedeTransicionarA funciona correctamente
     */
    public function test_puede_transicionar_a_method(): void
    {
        $appointment = Appointment::factory()->create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'employee_id' => $this->employee->id,
            'service_id' => $this->service->id,
            'estado' => Appointment::ESTADO_CONFIRMED,
        ]);

        // Transiciones válidas desde confirmed
        $this->assertTrue($appointment->puedeTransicionarA(Appointment::ESTADO_COMPLETED));
        $this->assertTrue($appointment->puedeTransicionarA(Appointment::ESTADO_CANCELLED));
        $this->assertTrue($appointment->puedeTransicionarA(Appointment::ESTADO_NO_SHOW));

        // Transiciones inválidas desde confirmed
        $this->assertFalse($appointment->puedeTransicionarA(Appointment::ESTADO_PENDING));
        $this->assertFalse($appointment->puedeTransicionarA(Appointment::ESTADO_CONFIRMED)); // mismo estado
    }

    /**
     * Test: Servicio cancelAppointment funciona correctamente
     */
    public function test_cancel_appointment_service_method(): void
    {
        $appointment = Appointment::factory()->confirmed()->create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'employee_id' => $this->employee->id,
            'service_id' => $this->service->id,
        ]);

        $result = $this->appointmentService->cancelAppointment(
            $appointment,
            $this->user->id,
            'No puedo asistir'
        );

        $this->assertEquals(Appointment::ESTADO_CANCELLED, $result->estado);
        $this->assertEquals($this->user->id, $result->cancelada_por_user_id);
        $this->assertEquals('No puedo asistir', $result->motivo_cancelacion);
        $this->assertNotNull($result->cancelada_en);
    }

    /**
     * Test: Transición actualiza timestamp correspondiente
     */
    public function test_transition_updates_correct_timestamp(): void
    {
        $appointment = Appointment::factory()->create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'employee_id' => $this->employee->id,
            'service_id' => $this->service->id,
            'estado' => Appointment::ESTADO_PENDING,
            'confirmada_en' => null,
        ]);

        // Confirmar
        $appointment->cambiarEstado(Appointment::ESTADO_CONFIRMED);
        $this->assertNotNull($appointment->confirmada_en);
        $this->assertNull($appointment->completada_en);
        $this->assertNull($appointment->cancelada_en);

        // Completar
        $appointment->cambiarEstado(Appointment::ESTADO_COMPLETED);
        $this->assertNotNull($appointment->completada_en);
    }
}
