<?php

namespace Tests\Feature\Api\Public;

use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\Employee;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessSearchTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function puede_listar_negocios_aprobados()
    {
        // Negocios aprobados
        $approvedBusinesses = Business::factory()->count(3)->create([
            'estado' => 'approved',
        ]);

        // Negocios no aprobados (no deben aparecer)
        Business::factory()->create(['estado' => 'pending']);
        Business::factory()->create(['estado' => 'suspended']);

        $response = $this->getJson('/api/v1/businesses');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');

        // Verificar que solo aparecen los aprobados
        $response->assertJsonFragment(['estado' => 'approved']);
    }

    /** @test */
    public function puede_filtrar_negocios_por_categoria()
    {
        Business::factory()->create([
            'estado' => 'approved',
            'categoria' => 'peluqueria',
        ]);

        Business::factory()->create([
            'estado' => 'approved',
            'categoria' => 'clinica',
        ]);

        Business::factory()->create([
            'estado' => 'approved',
            'categoria' => 'taller',
        ]);

        $response = $this->getJson('/api/v1/businesses?category=peluqueria');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['categoria' => 'peluqueria']);
    }

    /** @test */
    public function puede_buscar_negocios_por_nombre()
    {
        Business::factory()->create([
            'estado' => 'approved',
            'nombre' => 'Peluquería Estilos',
        ]);

        Business::factory()->create([
            'estado' => 'approved',
            'nombre' => 'Clínica Dental',
        ]);

        $response = $this->getJson('/api/v1/businesses?search=Estilos');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['nombre' => 'Peluquería Estilos']);
    }

    /** @test */
    public function puede_buscar_negocios_por_descripcion()
    {
        Business::factory()->create([
            'estado' => 'approved',
            'nombre' => 'Negocio A',
            'descripcion' => 'Servicios premium de belleza',
        ]);

        Business::factory()->create([
            'estado' => 'approved',
            'nombre' => 'Negocio B',
            'descripcion' => 'Clínica especializada',
        ]);

        $response = $this->getJson('/api/v1/businesses?search=belleza');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['nombre' => 'Negocio A']);
    }

    /** @test */
    public function puede_filtrar_negocios_por_ubicacion()
    {
        $business1 = Business::factory()->create(['estado' => 'approved']);
        BusinessLocation::factory()->create([
            'business_id' => $business1->id,
            'ciudad' => 'Ciudad de México',
            'activo' => true,
        ]);

        $business2 = Business::factory()->create(['estado' => 'approved']);
        BusinessLocation::factory()->create([
            'business_id' => $business2->id,
            'ciudad' => 'Guadalajara',
            'activo' => true,
        ]);

        $response = $this->getJson('/api/v1/businesses?location=Guadalajara');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function puede_ver_detalle_de_negocio()
    {
        $business = Business::factory()->create(['estado' => 'approved']);
        
        $location = BusinessLocation::factory()->create([
            'business_id' => $business->id,
            'activo' => true,
        ]);

        $response = $this->getJson("/api/v1/businesses/{$business->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'nombre',
                    'descripcion',
                    'telefono',
                    'email',
                    'categoria',
                    'estado',
                    'locations',
                ],
            ])
            ->assertJson([
                'data' => [
                    'id' => $business->id,
                    'nombre' => $business->nombre,
                ],
            ]);
    }

    /** @test */
    public function no_puede_ver_negocio_no_aprobado()
    {
        $business = Business::factory()->create(['estado' => 'pending']);

        $response = $this->getJson("/api/v1/businesses/{$business->id}");

        $response->assertStatus(404);
    }

    /** @test */
    public function puede_listar_servicios_de_negocio()
    {
        $business = Business::factory()->create(['estado' => 'approved']);

        // Servicios activos
        $activeServices = Service::factory()->count(2)->create([
            'business_id' => $business->id,
            'activo' => true,
        ]);

        // Servicio inactivo (no debe aparecer)
        Service::factory()->create([
            'business_id' => $business->id,
            'activo' => false,
        ]);

        $response = $this->getJson("/api/v1/businesses/{$business->id}/services");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function paginacion_funciona_correctamente()
    {
        Business::factory()->count(20)->create(['estado' => 'approved']);

        $response = $this->getJson('/api/v1/businesses');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'links',
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                ],
            ]);

        // Por defecto: 15 por página
        $this->assertCount(15, $response->json('data'));
    }

    /** @test */
    public function incluye_contadores_de_servicios_y_empleados()
    {
        $business = Business::factory()->create(['estado' => 'approved']);

        Service::factory()->count(3)->create(['business_id' => $business->id]);
        Employee::factory()->count(2)->create(['business_id' => $business->id]);

        $response = $this->getJson('/api/v1/businesses');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'total_services' => 3,
                'total_employees' => 2,
            ]);
    }
}
