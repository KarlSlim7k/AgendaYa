<?php

namespace Tests\Feature\Notifications;

use App\Jobs\SendAppointmentConfirmationJob;
use App\Mail\AppointmentConfirmation;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\Employee;
use App\Models\NotificationLog;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Business $business;
    protected BusinessLocation $location;
    protected Service $service;
    protected Employee $employee;
    protected Appointment $appointment;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear datos de prueba
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $this->business = Business::factory()->create();
        $this->location = BusinessLocation::factory()->create([
            'business_id' => $this->business->id,
        ]);

        $this->service = Service::factory()->create([
            'business_id' => $this->business->id,
            'duracion_minutos' => 30,
        ]);

        $this->employee = Employee::factory()->create([
            'business_id' => $this->business->id,
        ]);

        $this->appointment = Appointment::factory()->create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'employee_id' => $this->employee->id,
            'service_id' => $this->service->id,
            'estado' => Appointment::ESTADO_CONFIRMED,
        ]);
        
        // Cargar relaciones para tests
        $this->appointment->load(['user', 'business', 'service', 'employee']);
    }

    /** @test */
    public function job_enviar_confirmacion_despacha_correctamente()
    {
        Queue::fake();

        SendAppointmentConfirmationJob::dispatch($this->appointment);

        Queue::assertPushed(SendAppointmentConfirmationJob::class, function ($job) {
            return $job->appointment->id === $this->appointment->id;
        });
    }

    /** @test */
    public function job_envia_email_y_registra_log_exitosamente()
    {
        Mail::fake();

        $job = new SendAppointmentConfirmationJob($this->appointment);
        $job->handle();

        // Verificar que se envió el email
        Mail::assertSent(AppointmentConfirmation::class, function ($mail) {
            return $mail->appointment->id === $this->appointment->id &&
                   $mail->hasTo($this->user->email);
        });

        // Verificar que se registró en notification_logs
        $this->assertDatabaseHas('notification_logs', [
            'appointment_id' => $this->appointment->id,
            'user_id' => $this->user->id,
            'business_id' => $this->business->id,
            'tipo' => 'email',
            'evento' => 'confirmacion',
            'estado' => 'enviado',
        ]);

        $log = NotificationLog::withoutGlobalScope('business')
            ->where('appointment_id', $this->appointment->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('email', $log->tipo);
        $this->assertEquals('enviado', $log->estado);
        $this->assertEquals($this->user->email, $log->metadata['to']);
    }

    /** @test */
    public function job_registra_fallo_cuando_email_no_se_envia()
    {
        Mail::fake();

        // Simular fallo de email
        Mail::shouldReceive('to')
            ->once()
            ->andThrow(new \Exception('Error de SMTP'));

        $job = new SendAppointmentConfirmationJob($this->appointment);
        
        try {
            $job->handle();
        } catch (\Exception $e) {
            // Esperamos que lance excepción
        }

        // Verificar que se registró el fallo
        $this->assertDatabaseHas('notification_logs', [
            'appointment_id' => $this->appointment->id,
            'tipo' => 'email',
            'evento' => 'confirmacion',
            'estado' => 'reintentado',
        ]);

        $log = NotificationLog::withoutGlobalScope('business')
            ->where('appointment_id', $this->appointment->id)
            ->where('estado', 'reintentado')
            ->first();

        $this->assertNotNull($log);
        $this->assertStringContainsString('Error', $log->metadata['error'] ?? '');
    }

    /** @test */
    public function mailable_contiene_datos_correctos_de_cita()
    {
        $mailable = new AppointmentConfirmation($this->appointment);

        $mailable->assertHasSubject('Confirmación de Cita - ' . $this->business->nombre);
        $mailable->assertSeeInHtml($this->user->name);
        $mailable->assertSeeInHtml($this->business->nombre);
        $mailable->assertSeeInHtml($this->service->nombre);
        $mailable->assertSeeInHtml($this->employee->nombre);
        $mailable->assertSeeInHtml($this->appointment->codigo_confirmacion);
    }

    /** @test */
    public function notification_log_tiene_relaciones_correctas()
    {
        $log = NotificationLog::factory()->create([
            'user_id' => $this->user->id,
            'business_id' => $this->business->id,
            'appointment_id' => $this->appointment->id,
        ]);

        $this->assertInstanceOf(User::class, $log->user);
        $this->assertInstanceOf(Business::class, $log->business);
        $this->assertInstanceOf(Appointment::class, $log->appointment);

        $this->assertEquals($this->user->id, $log->user->id);
        $this->assertEquals($this->business->id, $log->business->id);
        $this->assertEquals($this->appointment->id, $log->appointment->id);
    }
}
