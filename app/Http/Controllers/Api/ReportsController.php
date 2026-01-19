<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportsService;
use App\Http\Resources\DashboardMetricsResource;
use App\Http\Resources\AppointmentReportResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class ReportsController extends Controller
{
    protected ReportsService $reportsService;

    public function __construct(ReportsService $reportsService)
    {
        $this->reportsService = $reportsService;
    }

    public function dashboard(Request $request): JsonResponse
    {
        $business = $request->user()->currentBusiness();

        if (!$business) {
            return response()->json(['message' => 'No se encontró negocio activo'], 404);
        }

        $metrics = $this->reportsService->getDashboardMetrics($business);

        return response()->json([
            'data' => new DashboardMetricsResource($metrics),
        ]);
    }

    public function appointments(Request $request): JsonResponse
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'service_id' => 'nullable|integer|exists:services,id',
            'employee_id' => 'nullable|integer|exists:employees,id',
            'estado' => 'nullable|in:pending,confirmed,completed,cancelled,no_show',
        ]);

        $business = $request->user()->currentBusiness();

        if (!$business) {
            return response()->json(['message' => 'No se encontró negocio activo'], 404);
        }

        $appointments = $this->reportsService->getAppointmentsReport(
            $business,
            Carbon::parse($request->fecha_inicio),
            Carbon::parse($request->fecha_fin),
            $request->service_id,
            $request->employee_id,
            $request->estado
        )->get();

        return response()->json([
            'data' => AppointmentReportResource::collection($appointments),
        ]);
    }

    public function topServices(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:20',
        ]);

        $business = $request->user()->currentBusiness();

        if (!$business) {
            return response()->json(['message' => 'No se encontró negocio activo'], 404);
        }

        $topServices = $this->reportsService->getTopServices(
            $business,
            $request->input('limit', 5)
        );

        return response()->json([
            'data' => $topServices,
        ]);
    }

    public function chartData(Request $request): JsonResponse
    {
        $business = $request->user()->currentBusiness();

        if (!$business) {
            return response()->json(['message' => 'No se encontró negocio activo'], 404);
        }

        $chartData = $this->reportsService->getAppointmentsChartData($business);

        return response()->json([
            'data' => $chartData,
        ]);
    }
}
