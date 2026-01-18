<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    public function __construct(
        private AvailabilityService $availabilityService
    ) {}

    /**
     * Get available slots for a service.
     * 
     * @param Request $request
     * @return JsonResponse
     * 
     * Query params:
     * - business_id: int (required)
     * - service_id: int (required)
     * - location_id: int (required)
     * - fecha_inicio: Y-m-d (required)
     * - fecha_fin: Y-m-d (optional, default: fecha_inicio)
     * - employee_id: int (optional)
     */
    public function slots(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'business_id' => ['required', 'integer', 'exists:businesses,id'],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'location_id' => ['required', 'integer', 'exists:business_locations,id'],
            'fecha_inicio' => ['required', 'date', 'after_or_equal:today'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
        ]);

        $fechaInicio = Carbon::parse($validated['fecha_inicio']);
        $fechaFin = $validated['fecha_fin'] 
            ? Carbon::parse($validated['fecha_fin']) 
            : $fechaInicio->copy();

        // Limitar rango máximo a 30 días
        if ($fechaInicio->diffInDays($fechaFin) > 30) {
            return response()->json([
                'message' => 'El rango de fechas no puede superar 30 días',
                'code' => 'DATE_RANGE_EXCEEDED'
            ], 422);
        }

        try {
            $slots = $this->availabilityService->generateSlots(
                $validated['business_id'],
                $validated['service_id'],
                $validated['location_id'],
                $fechaInicio,
                $fechaFin,
                $validated['employee_id'] ?? null
            );

            return response()->json([
                'data' => $slots->values(),
                'meta' => [
                    'total_slots' => $slots->count(),
                    'fecha_inicio' => $fechaInicio->format('Y-m-d'),
                    'fecha_fin' => $fechaFin->format('Y-m-d'),
                    'dias_consultados' => $fechaInicio->diffInDays($fechaFin) + 1,
                ]
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'code' => 'INVALID_PARAMETERS'
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al generar slots disponibles',
                'code' => 'SLOT_GENERATION_ERROR'
            ], 500);
        }
    }
}
