<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public $appointment;
    public $hoursUntil;

    public function __construct(Appointment $appointment, $hoursUntil = 24)
    {
        $this->appointment = $appointment;
        $this->hoursUntil = $hoursUntil;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $appointment = $this->appointment->load(['service', 'employee', 'business']);
        
        return (new MailMessage)
            ->subject('Recordatorio de Cita - ' . $appointment->business->nombre)
            ->greeting('¡Hola ' . $notifiable->name . '!')
            ->line('Te recordamos que tienes una cita próxima.')
            ->line('**Detalles de tu cita:**')
            ->line('**Servicio:** ' . $appointment->service->nombre)
            ->line('**Empleado:** ' . $appointment->employee->nombre)
            ->line('**Fecha:** ' . $appointment->fecha_hora_inicio->format('d/m/Y'))
            ->line('**Hora:** ' . $appointment->fecha_hora_inicio->format('H:i'))
            ->line('**Duración:** ' . $appointment->service->duracion_minutos . ' minutos')
            ->line('**Ubicación:** ' . $appointment->location->direccion)
            ->line('Por favor, llega 5 minutos antes.')
            ->action('Ver detalles', url('/appointments'))
            ->line('Si necesitas cancelar, hazlo con anticipación.')
            ->salutation('Te esperamos, ' . $appointment->business->nombre);
    }
}
