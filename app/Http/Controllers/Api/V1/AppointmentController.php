<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Appointment\StoreAppointmentRequest;
use App\Http\Requests\Appointment\UpdateAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Services\AppointmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AppointmentController extends Controller
{
    public function __construct(
        private AppointmentService $appointmentService
    ) {
        // Autorización se maneja por método con $this->authorize()
    }

    /**
     * Listar citas del usuario autenticado
     * 
     * GET /api/v1/appointments
     */
    public function index(Request $request): JsonResponse
    {
        // No requiere autorización adicional - solo ve sus propias citas
        $user = $request->user();
        
        $query = Appointment::with(['business', 'service', 'employee'])
            ->where('user_id', $user->id);

        // Filtros opcionales
        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('futuras') && $request->boolean('futuras')) {
            $query->futuras();
        }

        if ($request->has('pasadas') && $request->boolean('pasadas')) {
            $query->pasadas();
        }

        $appointments = $query->orderBy('fecha_hora_inicio', 'desc')->paginate(15);

        return response()->json([
            'data' => AppointmentResource::collection($appointments),
            'meta' => [
                'current_page' => $appointments->currentPage(),
                'per_page' => $appointments->perPage(),
                'total' => $appointments->total(),
            ],
        ]);
    }

    /**
     * Crear nueva cita
     * 
     * POST /api/v1/appointments
     */
    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        try {
            $appointment = $this->appointmentService->createAppointment([
                'business_id' => $request->business_id,
                'user_id' => $request->user()->id,
                'employee_id' => $request->employee_id,
                'service_id' => $request->service_id,
                'fecha_hora_inicio' => $request->fecha_hora_inicio,
                'notas_cliente' => $request->notas_cliente,
                'custom_data' => $request->custom_data,
            ]);

            Log::info('Appointment created', [
                'business_id' => $appointment->business_id,
                'appointment_id' => $appointment->id,
                'user_id' => $appointment->user_id,
                'employee_id' => $appointment->employee_id,
                'service_id' => $appointment->service_id,
                'fecha_hora' => $appointment->fecha_hora_inicio,
            ]);

            $appointment->load(['business', 'service', 'employee']);

            return response()->json([
                'message' => 'Cita creada exitosamente',
                'data' => new AppointmentResource($appointment),
            ], 201);

        } catch (\Exception $e) {
            Log::error('Appointment creation failed', [
                'user_id' => $request->user()->id,
                'business_id' => $request->business_id,
                'employee_id' => $request->employee_id,
                'service_id' => $request->service_id,
                'fecha_hora' => $request->fecha_hora_inicio,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Ver detalle de una cita
     * 
     * GET /api/v1/appointments/{id}
     */
    public function show(Request $request, Appointment $appointment): JsonResponse
    {
        // Policy: Verificar que el usuario puede ver esta cita
        $this->authorize('view', $appointment);

        $appointment->load(['business', 'service', 'employee', 'user']);

        return response()->json([
            'data' => new AppointmentResource($appointment),
        ]);
    }

    /**
     * Actualizar estado de cita (solo para staff del negocio)
     * 
     * PATCH /api/v1/appointments/{id}
     */
    public function update(UpdateAppointmentRequest $request, Appointment $appointment): JsonResponse
    {
        // Policy: Verificar que el usuario puede actualizar esta cita
        $this->authorize('update', $appointment);

        $user = $request->user();

        try {
            if ($request->has('estado')) {
                $appointment = $this->appointmentService->updateAppointmentStatus(
                    $appointment,
                    $request->estado,
                    [
                        'cancelada_por_user_id' => $request->estado === Appointment::ESTADO_CANCELLED 
                            ? $user->id 
                            : null,
                        'motivo_cancelacion' => $request->motivo_cancelacion,
                    ]
                );
            }

            if ($request->has('notas_internas')) {
                $appointment->notas_internas = $request->notas_internas;
                $appointment->save();
            }

            $appointment->load(['business', 'service', 'employee', 'user']);

            return response()->json([
                'message' => 'Cita actualizada exitosamente',
                'data' => new AppointmentResource($appointment),
            ]);

        } catch (\App\Exceptions\InvalidStateTransitionException $e) {
            throw $e;
        }
    }

    /**
     * Cancelar cita (usuario final o staff)
     * 
     * PATCH /api/v1/appointments/{id}/cancel
     */
    public function cancel(Request $request, Appointment $appointment): JsonResponse
    {
        // Policy: Verificar que el usuario puede cancelar esta cita
        $this->authorize('cancel', $appointment);

        $user = $request->user();

        $request->validate([
            'motivo_cancelacion' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $appointment = $this->appointmentService->cancelAppointment(
                $appointment,
                $user->id,
                $request->motivo_cancelacion
            );

            return response()->json([
                'message' => 'Cita cancelada exitosamente',
                'data' => new AppointmentResource($appointment),
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => 'No se puede cancelar la cita',
                'errors' => [
                    'estado' => [$e->getMessage()]
                ],
                'code' => 'INVALID_STATE_TRANSITION',
            ], 422);
        } catch (\App\Exceptions\InvalidStateTransitionException $e) {
            throw $e;
        }
    }
}