<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Schedule\StoreScheduleExceptionRequest;
use App\Http\Requests\Schedule\StoreScheduleTemplateRequest;
use App\Http\Resources\ScheduleExceptionResource;
use App\Http\Resources\ScheduleTemplateResource;
use App\Models\BusinessLocation;
use App\Models\ScheduleException;
use App\Models\ScheduleTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ScheduleController extends Controller
{
    /**
     * Display schedule templates for a location.
     */
    public function indexTemplates(BusinessLocation $location): AnonymousResourceCollection
    {
        // TODO: Implement RBAC Gate - $this->authorize('sucursal.read');

        // Verificar pertenencia al tenant
        if ($location->business_id !== auth()->user()->current_business_id) {
            abort(403, 'No autorizado para acceder a este recurso');
        }

        $templates = ScheduleTemplate::where('business_location_id', $location->id)
            ->orderBy('dia_semana')
            ->get();

        return ScheduleTemplateResource::collection($templates);
    }

    /**
     * Store or update schedule template.
     */
    public function storeTemplate(StoreScheduleTemplateRequest $request, BusinessLocation $location): JsonResponse
    {
        // Verificar pertenencia al tenant
        if ($location->business_id !== auth()->user()->current_business_id) {
            abort(403, 'No autorizado para modificar este recurso');
        }

        $data = array_merge($request->validated(), [
            'business_location_id' => $location->id,
        ]);

        // Upsert: Actualizar si existe, crear si no
        $template = ScheduleTemplate::updateOrCreate(
            [
                'business_location_id' => $location->id,
                'dia_semana' => $data['dia_semana'],
            ],
            $data
        );

        return (new ScheduleTemplateResource($template))
            ->response()
            ->setStatusCode($template->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Update schedule template.
     */
    public function updateTemplate(Request $request, ScheduleTemplate $template): ScheduleTemplateResource
    {
        // TODO: Implement RBAC Gate - $this->authorize('sucursal.update');

        // Verificar pertenencia al tenant a través de location
        if ($template->businessLocation->business_id !== auth()->user()->current_business_id) {
            abort(403, 'No autorizado para modificar este recurso');
        }

        $request->validate([
            'hora_apertura' => ['sometimes', 'date_format:H:i'],
            'hora_cierre' => ['sometimes', 'date_format:H:i', 'after:hora_apertura'],
            'activo' => ['sometimes', 'boolean'],
        ]);

        $template->update($request->only(['hora_apertura', 'hora_cierre', 'activo']));

        return new ScheduleTemplateResource($template->fresh());
    }

    /**
     * Display exceptions for a location.
     */
    public function indexExceptions(Request $request, BusinessLocation $location): AnonymousResourceCollection
    {
        // TODO: Implement RBAC Gate - $this->authorize('sucursal.read');

        // Verificar pertenencia al tenant
        if ($location->business_id !== auth()->user()->current_business_id) {
            abort(403, 'No autorizado para acceder a este recurso');
        }

        $exceptions = ScheduleException::where('business_location_id', $location->id)
            ->when($request->fecha_desde, fn($q) => $q->where('fecha', '>=', $request->fecha_desde))
            ->when($request->fecha_hasta, fn($q) => $q->where('fecha', '<=', $request->fecha_hasta))
            ->when($request->tipo, fn($q) => $q->where('tipo', $request->tipo))
            ->orderBy('fecha')
            ->get();

        return ScheduleExceptionResource::collection($exceptions);
    }

    /**
     * Store a new schedule exception.
     */
    public function storeException(StoreScheduleExceptionRequest $request, BusinessLocation $location): JsonResponse
    {
        // Verificar pertenencia al tenant
        if ($location->business_id !== auth()->user()->current_business_id) {
            abort(403, 'No autorizado para modificar este recurso');
        }

        $data = array_merge($request->validated(), [
            'business_location_id' => $location->id,
        ]);

        $exception = ScheduleException::create($data);

        return (new ScheduleExceptionResource($exception))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Remove schedule exception.
     */
    public function destroyException(ScheduleException $exception): JsonResponse
    {
        // TODO: Implement RBAC Gate - $this->authorize('sucursal.update');

        // Verificar pertenencia al tenant a través de location
        if ($exception->businessLocation->business_id !== auth()->user()->current_business_id) {
            abort(403, 'No autorizado para eliminar este recurso');
        }

        $exception->delete();

        return response()->json([
            'message' => 'Excepción eliminada exitosamente'
        ], 200);
    }
}
