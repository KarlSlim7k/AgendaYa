<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentConfirmed extends Notification implements ShouldQueue
{
    use Queueable;

    public $appointment;

    public function __construct(Appointment $appointment)
    {
        $this->appointment = $appointment;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $appointment = $this->appointment->load(['service', 'employee', 'business']);
        
        return (new MailMessage)
            ->subject('Confirmación de Cita - ' . $appointment->business->nombre)
            ->greeting('¡Hola ' . $notifiable->name . '!')
            ->line('Tu cita ha sido confirmada exitosamente.')
            ->line('**Detalles de la cita:**')
            ->line('**Servicio:** ' . $appointment->service->nombre)
            ->line('**Empleado:** ' . $appointment->employee->nombre)
            ->line('**Fecha:** ' . $appointment->fecha_hora_inicio->format('d/m/Y'))
            ->line('**Hora:** ' . $appointment->fecha_hora_inicio->format('H:i'))
            ->line('**Duración:** ' . $appointment->service->duracion_minutos . ' minutos')
            ->line('**Código de confirmación:** ' . $appointment->codigo_confirmacion)
            ->line('Por favor, llega 5 minutos antes de tu cita.')
            ->action('Ver mis citas', url('/appointments'))
            ->line('Si necesitas cancelar o reprogramar, por favor contacta al negocio con anticipación.')
            ->salutation('Saludos, ' . $appointment->business->nombre);
    }
}
