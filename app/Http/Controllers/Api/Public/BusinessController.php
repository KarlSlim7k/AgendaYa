<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\Public\BusinessPublicResource;
use App\Http\Resources\ServiceResource;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BusinessController extends Controller
{
    /**
     * List businesses with filters (public endpoint)
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Business::query()
            ->where('estado', 'approved') // Solo negocios aprobados
            ->with(['activeLocations'])
            ->withCount(['services', 'employees']);

        // Filtro por categoría
        if ($request->filled('category')) {
            $query->where('categoria', $request->category);
        }

        // Búsqueda por nombre o descripción
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%");
            });
        }

        // Filtro por ciudad (de sucursales)
        if ($request->filled('location')) {
            $location = $request->location;
            $query->whereHas('activeLocations', function ($q) use ($location) {
                $q->where('ciudad', 'like', "%{$location}%")
                  ->orWhere('estado', 'like', "%{$location}%");
            });
        }

        $businesses = $query->paginate(15);

        return BusinessPublicResource::collection($businesses);
    }

    /**
     * Get business detail (public endpoint)
     *
     * @param int $id
     * @return BusinessPublicResource
     */
    public function show(int $id): BusinessPublicResource
    {
        $business = Business::query()
            ->where('estado', 'approved')
            ->with(['activeLocations'])
            ->withCount(['services', 'employees'])
            ->findOrFail($id);

        return new BusinessPublicResource($business);
    }

    /**
     * List business services (public endpoint)
     *
     * @param int $id
     * @return AnonymousResourceCollection
     */
    public function services(int $id): AnonymousResourceCollection
    {
        $business = Business::query()
            ->where('estado', 'approved')
            ->findOrFail($id);

        $services = $business->services()
            ->where('activo', true)
            ->get();

        return ServiceResource::collection($services);
    }
}
