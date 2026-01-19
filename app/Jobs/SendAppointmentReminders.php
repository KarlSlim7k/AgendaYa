<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Notifications\AppointmentReminder;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendAppointmentReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        // Obtener citas confirmadas en las próximas 24 horas
        $now = Carbon::now();
        $in24Hours = $now->copy()->addHours(24);
        
        $appointments = Appointment::where('estado', 'confirmed')
            ->whereBetween('fecha_hora_inicio', [$now, $in24Hours])
            ->whereDoesntHave('notificationLogs', function ($query) use ($now) {
                $query->where('evento', 'recordatorio_24h')
                    ->where('estado', 'enviado')
                    ->where('created_at', '>=', $now->subHours(23));
            })
            ->with(['user', 'service', 'employee', 'business', 'location'])
            ->get();

        foreach ($appointments as $appointment) {
            try {
                $appointment->user->notify(new AppointmentReminder($appointment, 24));
                
                // Log de notificación (si existe la tabla)
                if (schema()->hasTable('notification_logs')) {
                    $appointment->notificationLogs()->create([
                        'user_id' => $appointment->user_id,
                        'business_id' => $appointment->business_id,
                        'tipo' => 'email',
                        'evento' => 'recordatorio_24h',
                        'estado' => 'enviado',
                        'intentos' => 1,
                        'ultimo_intento' => now(),
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Error enviando recordatorio para cita #' . $appointment->id . ': ' . $e->getMessage());
            }
        }
    }
}
