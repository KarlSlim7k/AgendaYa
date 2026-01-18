<?php

namespace Tests\Feature\Api;

use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\BusinessUserRole;
use App\Models\Role;
use App\Models\ScheduleException;
use App\Models\ScheduleTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ScheduleApiTest extends TestCase
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
        $this->adminUser->refresh(); // Refrescar para asegurar que tenga el current_business_id
    }

    /** @test */
    public function it_lists_schedule_templates_for_location()
    {
        Sanctum::actingAs($this->adminUser);

        ScheduleTemplate::create([
            'business_location_id' => $this->location->id,
            'dia_semana' => 1, // Lunes
            'hora_apertura' => '09:00',
            'hora_cierre' => '18:00',
            'activo' => true,
        ]);

        ScheduleTemplate::create([
            'business_location_id' => $this->location->id,
            'dia_semana' => 2, // Martes
            'hora_apertura' => '09:00',
            'hora_cierre' => '18:00',
            'activo' => true,
        ]);

        $response = $this->getJson("/api/v1/locations/{$this->location->id}/schedules");

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'dia_semana',
                        'dia_semana_nombre',
                        'hora_apertura',
                        'hora_cierre',
                        'activo',
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_creates_or_updates_schedule_template()
    {
        Sanctum::actingAs($this->adminUser);

        // Crear nuevo template
        $response = $this->postJson("/api/v1/locations/{$this->location->id}/schedules", [
            'dia_semana' => 1,
            'hora_apertura' => '08:00',
            'hora_cierre' => '17:00',
            'activo' => true,
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('schedule_templates', [
            'business_location_id' => $this->location->id,
            'dia_semana' => 1,
            'hora_apertura' => '08:00',
            'hora_cierre' => '17:00',
        ]);

        // Actualizar el mismo día (upsert)
        $response2 = $this->postJson("/api/v1/locations/{$this->location->id}/schedules", [
            'dia_semana' => 1,
            'hora_apertura' => '09:00',
            'hora_cierre' => '18:00',
            'activo' => true,
        ]);

        $response2->assertOk();

        $this->assertDatabaseHas('schedule_templates', [
            'business_location_id' => $this->location->id,
            'dia_semana' => 1,
            'hora_apertura' => '09:00',
            'hora_cierre' => '18:00',
        ]);

        // Verificar que solo existe un registro para dia_semana = 1
        $count = ScheduleTemplate::where('business_location_id', $this->location->id)
            ->where('dia_semana', 1)
            ->count();

        $this->assertEquals(1, $count);
    }

    /** @test */
    public function it_validates_hora_cierre_after_hora_apertura()
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->postJson("/api/v1/locations/{$this->location->id}/schedules", [
            'dia_semana' => 1,
            'hora_apertura' => '18:00',
            'hora_cierre' => '09:00', // Before apertura
            'activo' => true,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('hora_cierre');
    }

    /** @test */
    public function it_validates_dia_semana_range()
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->postJson("/api/v1/locations/{$this->location->id}/schedules", [
            'dia_semana' => 7, // Invalid (0-6 only)
            'hora_apertura' => '09:00',
            'hora_cierre' => '18:00',
            'activo' => true,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('dia_semana');
    }

    /** @test */
    public function it_lists_schedule_exceptions_for_location()
    {
        Sanctum::actingAs($this->adminUser);

        ScheduleException::create([
            'business_location_id' => $this->location->id,
            'fecha' => '2026-12-25',
            'tipo' => 'feriado',
            'motivo' => 'Navidad',
            'todo_el_dia' => true,
        ]);

        $response = $this->getJson("/api/v1/locations/{$this->location->id}/exceptions");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'fecha',
                        'tipo',
                        'motivo',
                        'todo_el_dia',
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_creates_schedule_exception()
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->postJson("/api/v1/locations/{$this->location->id}/exceptions", [
            'fecha' => '2026-12-25',
            'tipo' => 'feriado',
            'motivo' => 'Navidad',
            'todo_el_dia' => true,
        ]);

        $response->assertCreated();

        // Verificar que se creó (sin comparar fecha exacta por diferencias de formato)
        $this->assertDatabaseHas('schedule_exceptions', [
            'business_location_id' => $this->location->id,
            'tipo' => 'feriado',
            'motivo' => 'Navidad',
        ]);
    }

    /** @test */
    public function it_requires_hora_inicio_fin_when_not_todo_el_dia()
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->postJson("/api/v1/locations/{$this->location->id}/exceptions", [
            'fecha' => '2026-12-25',
            'tipo' => 'cierre',
            'motivo' => 'Cierre temporal',
            'todo_el_dia' => false,
            // Missing hora_inicio and hora_fin
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['hora_inicio', 'hora_fin']);
    }

    /** @test */
    public function it_deletes_schedule_exception()
    {
        Sanctum::actingAs($this->adminUser);

        $exception = ScheduleException::create([
            'business_location_id' => $this->location->id,
            'fecha' => '2026-12-25',
            'tipo' => 'feriado',
            'motivo' => 'Navidad',
            'todo_el_dia' => true,
        ]);

        $response = $this->deleteJson("/api/v1/exceptions/{$exception->id}");

        $response->assertOk();

        $this->assertDatabaseMissing('schedule_exceptions', [
            'id' => $exception->id,
        ]);
    }

    /** @test */
    public function it_prevents_accessing_schedules_from_another_business()
    {
        Sanctum::actingAs($this->adminUser);

        // Crear otra sucursal de otro negocio
        $otherBusiness = Business::factory()->create();
        $otherLocation = BusinessLocation::factory()->create([
            'business_id' => $otherBusiness->id,
        ]);

        ScheduleTemplate::create([
            'business_location_id' => $otherLocation->id,
            'dia_semana' => 1,
            'hora_apertura' => '09:00',
            'hora_cierre' => '18:00',
            'activo' => true,
        ]);

        $response = $this->getJson("/api/v1/locations/{$otherLocation->id}/schedules");

        // BusinessLocation no se encuentra debido a Global Scope o validación
        $response->assertNotFound();
    }
}
