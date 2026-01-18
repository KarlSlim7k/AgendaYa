<?php

namespace Tests\Feature\Api;

use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\BusinessUserRole;
use App\Models\Employee;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmployeeApiTest extends TestCase
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

        $this->business = Business::factory()->create(['estado' => 'approved']);
        $this->location = BusinessLocation::factory()->create([
            'business_id' => $this->business->id,
        ]);

        $this->adminUser = User::factory()->create();
        $adminRole = Role::where('nombre', 'NEGOCIO_ADMIN')->first();

        BusinessUserRole::create([
            'user_id' => $this->adminUser->id,
            'business_id' => $this->business->id,
            'role_id' => $adminRole->id,
        ]);

        // Set current_business_id
        $this->adminUser->current_business_id = $this->business->id;
        $this->adminUser->save();
        $this->adminUser->refresh();
    }

    /** @test */
    public function it_creates_employee_with_assigned_services()
    {
        Sanctum::actingAs($this->adminUser);

        $services = Service::factory()->count(2)->create([
            'business_id' => $this->business->id,
        ]);

        $employeeData = [
            'nombre' => 'Juan Pérez',
            'email' => 'juan.perez@test.com',
            'telefono' => '+52 55 1234 5678',
            'estado' => 'disponible',
            'service_ids' => $services->pluck('id')->toArray(),
        ];

        $response = $this->postJson('/api/v1/employees', $employeeData);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'nombre',
                    'email',
                    'estado',
                    'servicios_count',
                ],
            ]);

        $employee = Employee::where('email', 'juan.perez@test.com')->first();

        $this->assertNotNull($employee);
        $this->assertCount(2, $employee->servicios);
    }

    /** @test */
    public function it_updates_employee_and_syncs_services()
    {
        Sanctum::actingAs($this->adminUser);

        $employee = Employee::factory()->create([
            'business_id' => $this->business->id,
        ]);

        $oldService = Service::factory()->create([
            'business_id' => $this->business->id,
        ]);

        $employee->servicios()->attach($oldService->id);

        $newServices = Service::factory()->count(2)->create([
            'business_id' => $this->business->id,
        ]);

        $response = $this->putJson("/api/v1/employees/{$employee->id}", [
            'nombre' => 'Updated Name',
            'email' => $employee->email,
            'estado' => 'disponible',
            'service_ids' => $newServices->pluck('id')->toArray(),
        ]);

        $response->assertOk();

        $employee->refresh();

        $this->assertEquals('Updated Name', $employee->nombre);
        $this->assertCount(2, $employee->servicios);
        $this->assertFalse($employee->servicios->contains($oldService->id));
    }

    /** @test */
    public function it_returns_employee_with_servicios_count()
    {
        Sanctum::actingAs($this->adminUser);

        $employee = Employee::factory()->create([
            'business_id' => $this->business->id,
        ]);

        $services = Service::factory()->count(3)->create([
            'business_id' => $this->business->id,
        ]);

        $employee->servicios()->attach($services->pluck('id'));

        $response = $this->getJson("/api/v1/employees/{$employee->id}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $employee->id,
                    'servicios_count' => 3,
                ],
            ]);
    }

    /** @test */
    public function it_lists_employees_with_pagination()
    {
        Sanctum::actingAs($this->adminUser);

        Employee::factory()->count(5)->create([
            'business_id' => $this->business->id,
        ]);

        $response = $this->getJson('/api/v1/employees?per_page=3');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'nombre', 'email', 'estado'],
                ],
                'meta' => ['current_page', 'per_page', 'total'],
            ]);
    }

    /** @test */
    public function it_filters_employees_by_estado()
    {
        Sanctum::actingAs($this->adminUser);

        Employee::factory()->count(2)->create([
            'business_id' => $this->business->id,
            'estado' => 'disponible',
        ]);

        Employee::factory()->create([
            'business_id' => $this->business->id,
            'estado' => 'vacaciones',
        ]);

        $response = $this->getJson('/api/v1/employees?estado=disponible');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function it_deletes_employee_successfully()
    {
        Sanctum::actingAs($this->adminUser);

        $employee = Employee::factory()->create([
            'business_id' => $this->business->id,
        ]);

        $response = $this->deleteJson("/api/v1/employees/{$employee->id}");

        $response->assertOk();

        $this->assertSoftDeleted('employees', [
            'id' => $employee->id,
        ]);
    }

    /** @test */
    public function it_validates_email_uniqueness_per_business()
    {
        Sanctum::actingAs($this->adminUser);

        // Crear servicio primero para que pase la validación de service_ids
        $service = Service::factory()->create([
            'business_id' => $this->business->id,
        ]);

        Employee::factory()->create([
            'business_id' => $this->business->id,
            'email' => 'duplicate@test.com',
        ]);

        $response = $this->postJson('/api/v1/employees', [
            'nombre' => 'Test Employee',
            'email' => 'duplicate@test.com',
            'estado' => 'disponible',
            'service_ids' => [$service->id],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }
}
