<?php

namespace App\Services;

use App\Exceptions\InvalidStateTransitionException;
use App\Exceptions\SlotNotAvailableException;
use App\Jobs\SendAppointmentConfirmationJob;
use App\Models\Appointment;
use App\Models\Employee;
use App\Models\Service;
use App\Notifications\AppointmentConfirmed;
use App\Notifications\AppointmentCancelled;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Servicio para gestión de citas con validación de disponibilidad
 */
class AppointmentService
{
    public function __construct(
        private AvailabilityService $availabilityService
    ) {}

    /**
     * Crear una nueva cita con validación de disponibilidad y lock pessimista
     *
     * @param array $data
     * @return Appointment
     * @throws SlotNotAvailableException
     */
    public function createAppointment(array $data): Appointment
    {
        return DB::transaction(function () use ($data) {
            $service = Service::findOrFail($data['service_id']);
            $fechaHoraInicio = Carbon::parse($data['fecha_hora_inicio']);
            $fechaHoraFin = $fechaHoraInicio->copy()->addMinutes($service->duracion_minutos);

            // LOCK PESSIMISTA: Bloquear citas existentes durante validación
            // Definir ventana de búsqueda con buffers
            $startWindow = $fechaHoraInicio->copy()->subMinutes($service->buffer_pre_minutos);
            $endWindow = $fechaHoraFin->copy()->addMinutes($service->buffer_post_minutos);

            $existingAppointments = Appointment::where('employee_id', $data['employee_id'])
                ->whereBetween('fecha_hora_inicio', [$startWindow, $endWindow])
                ->whereIn('estado', [Appointment::ESTADO_PENDING, Appointment::ESTADO_CONFIRMED])
                ->lockForUpdate()
                ->get();

            // Validar solapamiento con buffers
            foreach ($existingAppointments as $existing) {
                if ($this->overlapsWithBuffers($existing, $fechaHoraInicio, $fechaHoraFin, $service)) {
                    throw new SlotNotAvailableException('El horario ya no está disponible debido a otra cita');
                }
            }

            // Validar disponibilidad con el motor (reglas R1-R7)
            $isAvailable = $this->availabilityService->validateSlot(
                $data['business_id'],
                $data['service_id'],
                $data['employee_id'],
                $fechaHoraInicio
            );

            if (!$isAvailable) {
                throw new SlotNotAvailableException('El horario seleccionado no está dentro del horario laboral');
            }

            // Crear la cita
            $appointment = Appointment::create([
                'business_id' => $data['business_id'],
                'user_id' => $data['user_id'],
                'employee_id' => $data['employee_id'],
                'service_id' => $data['service_id'],
                'fecha_hora_inicio' => $fechaHoraInicio,
                'fecha_hora_fin' => $fechaHoraFin,
                'estado' => $service->requiere_confirmacion 
                    ? Appointment::ESTADO_PENDING 
                    : Appointment::ESTADO_CONFIRMED,
                'notas_cliente' => $data['notas_cliente'] ?? null,
                'custom_data' => $data['custom_data'] ?? null,
                'confirmada_en' => !$service->requiere_confirmacion ? now() : null,
            ]);

            // Invalidar caché de disponibilidad
            $this->invalidateAvailabilityCache($data['business_id'], $data['service_id'], $fechaHoraInicio);

            // Enviar notificación de confirmación
            $appointment->load(['user', 'service', 'employee', 'business']);
            $appointment->user->notify(new AppointmentConfirmed($appointment));

            return $appointment;
        });
    }

    /**
     * Actualizar estado de una cita
     *
     * @param Appointment $appointment
     * @param string $nuevoEstado
     * @param array $datosAdicionales
     * @return Appointment
     * @throws InvalidStateTransitionException
     */
    public function updateAppointmentStatus(
        Appointment $appointment,
        string $nuevoEstado,
        array $datosAdicionales = []
    ): Appointment {
        return DB::transaction(function () use ($appointment, $nuevoEstado, $datosAdicionales) {
            $appointment->cambiarEstado($nuevoEstado, $datosAdicionales);

            // Invalidar caché si se cancela
            if ($nuevoEstado === Appointment::ESTADO_CANCELLED) {
                $this->invalidateAvailabilityCache(
                    $appointment->business_id,
                    $appointment->service_id,
                    $appointment->fecha_hora_inicio
                );
            }

            return $appointment->fresh();
        });
    }

    /**
     * Cancelar una cita
     *
     * @param Appointment $appointment
     * @param int $cancelledByUserId
     * @param string|null $motivo
     * @return Appointment
     */
    public function cancelAppointment(
        Appointment $appointment,
        int $cancelledByUserId,
        ?string $motivo = null
    ): Appointment {
        $result = $this->updateAppointmentStatus(
            $appointment,
            Appointment::ESTADO_CANCELLED,
            [
                'cancelada_por_user_id' => $cancelledByUserId,
                'motivo_cancelacion' => $motivo,
            ]
        );

        // Enviar notificación de cancelación
        $appointment->load(['user', 'service', 'employee', 'business']);
        $appointment->user->notify(new AppointmentCancelled($appointment, $motivo));

        return $result;
    }

    /**
     * Verificar si hay solapamiento considerando buffers
     *
     * @param Appointment $existing
     * @param Carbon $newStart
     * @param Carbon $newEnd
     * @param Service $service
     * @return bool
     */
    private function overlapsWithBuffers(
        Appointment $existing,
        Carbon $newStart,
        Carbon $newEnd,
        Service $service
    ): bool {
        $existingService = $existing->service;
        
        // Calcular ventanas con buffers
        $existingStart = $existing->fecha_hora_inicio->copy()
            ->subMinutes($existingService->buffer_pre_minutos);
        $existingEnd = $existing->fecha_hora_fin->copy()
            ->addMinutes($existingService->buffer_post_minutos);
        
        $newStartWithBuffer = $newStart->copy()->subMinutes($service->buffer_pre_minutos);
        $newEndWithBuffer = $newEnd->copy()->addMinutes($service->buffer_post_minutos);

        // Verificar solapamiento
        return !($newEndWithBuffer->lte($existingStart) || $newStartWithBuffer->gte($existingEnd));
    }

    /**
     * Invalidar caché de disponibilidad
     *
     * @param int $businessId
     * @param int $serviceId
     * @param Carbon $fecha
     * @return void
     */
    private function invalidateAvailabilityCache(int $businessId, int $serviceId, Carbon $fecha): void
    {
        $cacheKey = "slots:{$businessId}:{$serviceId}:{$fecha->format('Y-m-d')}";
        cache()->forget($cacheKey);
        
        // Invalidar por tenant
        cache()->tags(['tenant_' . $businessId])->flush();
    }
}
