<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Service\StoreServiceRequest;
use App\Http\Requests\Service\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServiceController extends Controller
{
    /**
     * Display a listing of services for the authenticated business.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('servicio.read');

        $services = Service::query()
            ->when($request->activo !== null, fn($q) => $q->where('activo', $request->boolean('activo')))
            ->when($request->search, fn($q) => $q->where('nombre', 'like', "%{$request->search}%"))
            ->with('employees:id,nombre')
            ->withCount('employees')
            ->paginate($request->per_page ?? 15);

        return ServiceResource::collection($services);
    }

    /**
     * Store a newly created service.
     */
    public function store(StoreServiceRequest $request): JsonResponse
    {
        $service = Service::create(array_merge(
            $request->validated(),
            ['business_id' => auth()->user()->current_business_id]
        ));

        return (new ServiceResource($service))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified service.
     */
    public function show(Service $service): ServiceResource
    {
        $this->authorize('servicio.read');

        // Verificar pertenencia al tenant
        if ($service->business_id !== auth()->user()->current_business_id) {
            abort(403, 'No autorizado para acceder a este recurso');
        }

        $service->load('employees:id,nombre,email');

        return new ServiceResource($service);
    }

    /**
     * Update the specified service.
     */
    public function update(UpdateServiceRequest $request, Service $service): ServiceResource
    {
        // Verificar pertenencia al tenant
        if ($service->business_id !== auth()->user()->current_business_id) {
            abort(403, 'No autorizado para modificar este recurso');
        }

        $service->update($request->validated());

        return new ServiceResource($service->fresh());
    }

    /**
     * Remove the specified service (soft delete).
     */
    public function destroy(Service $service): JsonResponse
    {
        $this->authorize('servicio.delete');

        // Verificar pertenencia al tenant
        if ($service->business_id !== auth()->user()->current_business_id) {
            abort(403, 'No autorizado para eliminar este recurso');
        }

        // Verificar si tiene empleados asignados
        if ($service->employees()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar el servicio porque tiene empleados asignados',
                'code' => 'SERVICE_HAS_EMPLOYEES'
            ], 422);
        }

        $service->delete();

        return response()->json([
            'message' => 'Servicio eliminado correctamente'
        ], 200);
    }
}
