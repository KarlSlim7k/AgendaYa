<?php

namespace Tests\Feature\Api;

use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\BusinessUserRole;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ServiceApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Business $business;
    protected BusinessLocation $location;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles and permissions
        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\RolesAndPermissionsSeeder']);

        // Crear negocio y sucursal
        $this->business = Business::factory()->create([
            'nombre' => 'Peluquería Test API',
            'estado' => 'approved',
        ]);

        $this->location = BusinessLocation::factory()->create([
            'business_id' => $this->business->id,
            'nombre' => 'Sucursal Centro',
        ]);

        // Crear usuario admin del negocio
        $this->adminUser = User::factory()->create();

        // Asignar rol NEGOCIO_ADMIN
        $adminRole = Role::where('nombre', 'NEGOCIO_ADMIN')->first();
        BusinessUserRole::create([
            'user_id' => $this->adminUser->id,
            'business_id' => $this->business->id,
            'role_id' => $adminRole->id,
        ]);

        // Set current_business_id
        $this->adminUser->current_business_id = $this->business->id;
        $this->adminUser->save();
        $this->adminUser->refresh(); // Refrescar para asegurar que tenga el current_business_id
    }

    /** @test */
    public function it_lists_services_for_authenticated_user()
    {
        Sanctum::actingAs($this->adminUser);

        Service::factory()->count(3)->create([
            'business_id' => $this->business->id,
        ]);

        $response = $this->getJson('/api/v1/services');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'nombre',
                        'descripcion',
                        'duracion_minutos',
                        'precio',
                        'activo',
                        'empleados_count',
                    ],
                ],
                'links',
                'meta',
            ])
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_creates_service_with_valid_data()
    {
        Sanctum::actingAs($this->adminUser);

        $serviceData = [
            'nombre' => 'Corte de cabello premium',
            'descripcion' => 'Servicio premium con lavado incluido',
            'duracion_minutos' => 45,
            'precio' => 250.00,
            'buffer_pre_minutos' => 5,
            'buffer_post_minutos' => 10,
            'activo' => true,
        ];

        $response = $this->postJson('/api/v1/services', $serviceData);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'nombre',
                    'duracion_minutos',
                    'precio',
                ],
            ]);

        $this->assertDatabaseHas('services', [
            'nombre' => 'Corte de cabello premium',
            'business_id' => $this->business->id,
            'precio' => 250.00,
        ]);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_service()
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->postJson('/api/v1/services', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'nombre',
                'duracion_minutos',
                'precio',
            ]);
    }

    /** @test */
    public function it_validates_duracion_minutos_minimum()
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->postJson('/api/v1/services', [
            'nombre' => 'Test Service',
            'duracion_minutos' => 10, // Less than minimum 15
            'precio' => 100,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('duracion_minutos');
    }

    /** @test */
    public function it_updates_service_successfully()
    {
        Sanctum::actingAs($this->adminUser);

        $service = Service::factory()->create([
            'business_id' => $this->business->id,
            'nombre' => 'Nombre original',
            'precio' => 100.00,
        ]);

        $response = $this->putJson("/api/v1/services/{$service->id}", [
            'nombre' => 'Nombre actualizado',
            'precio' => 150.00,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'nombre' => 'Nombre actualizado',
            'precio' => 150.00,
        ]);
    }

    /** @test */
    public function it_prevents_updating_service_from_another_business()
    {
        Sanctum::actingAs($this->adminUser);

        // Crear otro negocio
        $otherBusiness = Business::factory()->create();
        $otherLocation = BusinessLocation::factory()->create([
            'business_id' => $otherBusiness->id,
        ]);

        $serviceFromOtherBusiness = Service::factory()->create([
            'business_id' => $otherBusiness->id,
        ]);

        $response = $this->putJson("/api/v1/services/{$serviceFromOtherBusiness->id}", [
            'nombre' => 'Intento de actualización',
        ]);

        $response->assertNotFound(); // Global Scope filters it out
    }

    /** @test */
    public function it_deletes_service_successfully()
    {
        Sanctum::actingAs($this->adminUser);

        $service = Service::factory()->create([
            'business_id' => $this->business->id,
        ]);

        $response = $this->deleteJson("/api/v1/services/{$service->id}");

        $response->assertOk()
            ->assertJson([
                'message' => 'Servicio eliminado correctamente',
            ]);

        // Verificar soft delete - el registro existe pero tiene deleted_at
        $this->assertSoftDeleted('services', [
            'id' => $service->id,
        ]);
    }

    /** @test */
    public function it_requires_authentication_for_protected_endpoints()
    {
        // Sin autenticación
        $response = $this->getJson('/api/v1/services');

        $response->assertUnauthorized();
    }

    /** @test */
    public function it_filters_services_by_search_term()
    {
        Sanctum::actingAs($this->adminUser);

        Service::factory()->create([
            'business_id' => $this->business->id,
            'nombre' => 'Corte de cabello',
        ]);

        Service::factory()->create([
            'business_id' => $this->business->id,
            'nombre' => 'Tinte completo',
        ]);

        $response = $this->getJson('/api/v1/services?search=corte');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nombre', 'Corte de cabello');
    }

    /** @test */
    public function it_filters_services_by_activo_status()
    {
        Sanctum::actingAs($this->adminUser);

        Service::factory()->create([
            'business_id' => $this->business->id,
            'activo' => true,
        ]);

        Service::factory()->count(2)->create([
            'business_id' => $this->business->id,
            'activo' => false,
        ]);

        $response = $this->getJson('/api/v1/services?activo=1');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
