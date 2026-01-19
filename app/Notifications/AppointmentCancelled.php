<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentCancelled extends Notification implements ShouldQueue
{
    use Queueable;

    public $appointment;
    public $reason;

    public function __construct(Appointment $appointment, $reason = null)
    {
        $this->appointment = $appointment;
        $this->reason = $reason;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $appointment = $this->appointment->load(['service', 'employee', 'business']);
        
        $mail = (new MailMessage)
            ->subject('Cita Cancelada - ' . $appointment->business->nombre)
            ->greeting('Hola ' . $notifiable->name)
            ->line('Tu cita ha sido cancelada.')
            ->line('**Detalles de la cita cancelada:**')
            ->line('**Servicio:** ' . $appointment->service->nombre)
            ->line('**Fecha:** ' . $appointment->fecha_hora_inicio->format('d/m/Y'))
            ->line('**Hora:** ' . $appointment->fecha_hora_inicio->format('H:i'));
        
        if ($this->reason) {
            $mail->line('**Motivo:** ' . $this->reason);
        }
        
        return $mail->line('Si deseas reprogramar, puedes hacer una nueva reserva en cualquier momento.')
            ->action('Reservar nueva cita', url('/appointments/create'))
            ->salutation('Saludos, ' . $appointment->business->nombre);
    }
}
