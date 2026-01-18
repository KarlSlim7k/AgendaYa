<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    /**
     * Display a listing of employees for the authenticated business.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('empleado.read');

        $employees = Employee::query()
            ->when($request->estado, fn($q) => $q->where('estado', $request->estado))
            ->when($request->search, fn($q) => $q->where('nombre', 'like', "%{$request->search}%"))
            ->when($request->service_id, fn($q) => $q->conServicio($request->service_id))
            ->with('services:id,nombre,precio')
            ->withCount('services')
            ->paginate($request->per_page ?? 15);

        return EmployeeResource::collection($employees);
    }

    /**
     * Store a newly created employee.
     */
    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $employee = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $serviceIds = $data['service_ids'];
            unset($data['service_ids']);

            // Agregar business_id del usuario autenticado
            $data['business_id'] = auth()->user()->current_business_id;

            $employee = Employee::create($data);
            
            // Asignar servicios
            $employee->services()->attach($serviceIds);

            return $employee->load('services:id,nombre');
        });

        return (new EmployeeResource($employee))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified employee.
     */
    public function show(Employee $employee): EmployeeResource
    {
        $this->authorize('empleado.read');

        // Verificar pertenencia al tenant
        if ($employee->business_id !== auth()->user()->current_business_id) {
            abort(403, 'No autorizado para acceder a este recurso');
        }

        $employee->load('services:id,nombre,precio,duracion_minutos');

        return new EmployeeResource($employee);
    }

    /**
     * Update the specified employee.
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee): EmployeeResource
    {
        // Verificar pertenencia al tenant
        if ($employee->business_id !== auth()->user()->current_business_id) {
            abort(403, 'No autorizado para modificar este recurso');
        }

        DB::transaction(function () use ($request, $employee) {
            $data = $request->validated();
            
            if (isset($data['service_ids'])) {
                $serviceIds = $data['service_ids'];
                unset($data['service_ids']);
                
                // Sincronizar servicios
                $employee->services()->sync($serviceIds);
            }

            $employee->update($data);
        });

        return new EmployeeResource($employee->fresh()->load('services:id,nombre'));
    }

    /**
     * Remove the specified employee (soft delete).
     */
    public function destroy(Employee $employee): JsonResponse
    {
        $this->authorize('empleado.delete');

        // Verificar pertenencia al tenant
        if ($employee->business_id !== auth()->user()->current_business_id) {
            abort(403, 'No autorizado para eliminar este recurso');
        }

        // Verificar si tiene citas futuras (cuando exista la tabla appointments)
        // TODO: Agregar validación de citas futuras

        $employee->delete();

        return response()->json([
            'message' => 'Empleado eliminado exitosamente'
        ], 200);
    }
}
