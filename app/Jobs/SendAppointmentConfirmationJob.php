<?php

namespace App\Jobs;

use App\Mail\AppointmentConfirmation;
use App\Models\Appointment;
use App\Models\NotificationLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Exception;

class SendAppointmentConfirmationJob implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Appointment $appointment
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Cargar relaciones necesarias
            $this->appointment->load(['user', 'business', 'service', 'employee']);

            // Enviar email
            Mail::to($this->appointment->user->email)
                ->send(new AppointmentConfirmation($this->appointment));

            // Registrar envío exitoso
            NotificationLog::withoutGlobalScope('business')->create([
                'user_id' => $this->appointment->user_id,
                'business_id' => $this->appointment->business_id,
                'appointment_id' => $this->appointment->id,
                'tipo' => 'email',
                'evento' => 'confirmacion',
                'estado' => 'enviado',
                'intentos' => $this->attempts(),
                'ultimo_intento' => now(),
                'metadata' => [
                    'to' => $this->appointment->user->email,
                    'subject' => 'Confirmación de Cita',
                ],
            ]);

            Log::info('Notificación de confirmación enviada', [
                'appointment_id' => $this->appointment->id,
                'user_email' => $this->appointment->user->email,
            ]);

        } catch (Exception $e) {
            // Registrar fallo
            NotificationLog::withoutGlobalScope('business')->create([
                'user_id' => $this->appointment->user_id,
                'business_id' => $this->appointment->business_id,
                'appointment_id' => $this->appointment->id,
                'tipo' => 'email',
                'evento' => 'confirmacion',
                'estado' => $this->attempts() >= $this->tries ? 'fallido' : 'reintentado',
                'intentos' => $this->attempts(),
                'ultimo_intento' => now(),
                'metadata' => [
                    'error' => $e->getMessage(),
                    'to' => $this->appointment->user->email,
                ],
            ]);

            Log::error('Error enviando notificación de confirmación', [
                'appointment_id' => $this->appointment->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // Relanzar excepción para reintentar
            if ($this->attempts() < $this->tries) {
                throw $e;
            }
        }
    }
}
