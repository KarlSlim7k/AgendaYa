<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\BusinessUserRole;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\ScheduleTemplate;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Tests de aislamiento multi-tenant para citas
 */
class AppointmentRBACTest extends TestCase
{
    use RefreshDatabase;

    private Business $business1;
    private Business $business2;
    private User $userFinal;
    private User $staffBusiness1;
    private User $adminBusiness1;
    private Service $service1;
    private Service $service2;
    private Employee $employee1;
    private Employee $employee2;
    private Appointment $appointment1;
    private Appointment $appointment2;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear negocios
        $this->business1 = Business::factory()->create(['estado' => 'approved']);
        $this->business2 = Business::factory()->create(['estado' => 'approved']);

        // Crear locations
        $location1 = BusinessLocation::factory()->create([
            'business_id' => $this->business1->id,
            'activo' => true,
        ]);
        $location2 = BusinessLocation::factory()->create([
            'business_id' => $this->business2->id,
            'activo' => true,
        ]);

        // Crear servicios
        $this->service1 = Service::factory()->create([
            'business_id' => $this->business1->id,
            'duracion_minutos' => 30,
        ]);
        $this->service2 = Service::factory()->create([
            'business_id' => $this->business2->id,
            'duracion_minutos' => 30,
        ]);

        // Crear empleados
        $this->employee1 = Employee::factory()->create([
            'business_id' => $this->business1->id,
        ]);
        $this->employee1->services()->attach($this->service1->id);

        $this->employee2 = Employee::factory()->create([
            'business_id' => $this->business2->id,
        ]);
        $this->employee2->services()->attach($this->service2->id);

        // Crear horarios
        foreach ([$location1, $location2] as $location) {
            for ($dia = 1; $dia <= 5; $dia++) {
                ScheduleTemplate::factory()->create([
                    'business_location_id' => $location->id,
                    'dia_semana' => $dia,
                    'hora_apertura' => '09:00',
                    'hora_cierre' => '18:00',
                    'activo' => true,
                ]);
            }
        }

        // Crear usuarios
        $this->userFinal = User::factory()->create();
        $this->staffBusiness1 = User::factory()->create([
            'current_business_id' => $this->business1->id,
        ]);
        $this->adminBusiness1 = User::factory()->create([
            'current_business_id' => $this->business1->id,
        ]);

        // Crear roles y permisos
        $this->setupRolesAndPermissions();

        // Crear citas de prueba
        $fecha = Carbon::now()->next(Carbon::MONDAY)->setHour(10)->setMinute(0);

        $this->appointment1 = Appointment::factory()->create([
            'business_id' => $this->business1->id,
            'user_id' => $this->userFinal->id,
            'employee_id' => $this->employee1->id,
            'service_id' => $this->service1->id,
            'fecha_hora_inicio' => $fecha,
            'fecha_hora_fin' => $fecha->copy()->addMinutes(30),
            'estado' => Appointment::ESTADO_CONFIRMED,
        ]);

        $this->appointment2 = Appointment::factory()->create([
            'business_id' => $this->business2->id,
            'user_id' => $this->userFinal->id,
            'employee_id' => $this->employee2->id,
            'service_id' => $this->service2->id,
            'fecha_hora_inicio' => $fecha->copy()->addHours(2),
            'fecha_hora_fin' => $fecha->copy()->addHours(2)->addMinutes(30),
            'estado' => Appointment::ESTADO_CONFIRMED,
        ]);
    }

    private function setupRolesAndPermissions(): void
    {
        // Crear roles
        $roleStaff = Role::create([
            'nombre' => 'NEGOCIO_STAFF', 
            'display_name' => 'Staff del Negocio',
            'descripcion' => 'Staff',
            'nivel_jerarquia' => 1,
        ]);
        $roleAdmin = Role::create([
            'nombre' => 'NEGOCIO_ADMIN', 
            'display_name' => 'Administrador del Negocio',
            'descripcion' => 'Admin',
            'nivel_jerarquia' => 3,
        ]);

        // Crear permisos
        $permCitaRead = Permission::create([
            'nombre' => 'cita.read', 
            'display_name' => 'Leer citas',
            'descripcion' => 'Leer citas',
            'modulo' => 'cita',
            'accion' => 'read',
        ]);
        $permCitaUpdate = Permission::create([
            'nombre' => 'cita.update', 
            'display_name' => 'Actualizar citas',
            'descripcion' => 'Actualizar citas',
            'modulo' => 'cita',
            'accion' => 'update',
        ]);
        $permCitaDelete = Permission::create([
            'nombre' => 'cita.delete', 
            'display_name' => 'Eliminar citas',
            'descripcion' => 'Eliminar citas',
            'modulo' => 'cita',
            'accion' => 'delete',
        ]);

        // Asignar permisos a roles
        $roleStaff->permissions()->attach([$permCitaRead->id, $permCitaUpdate->id]);
        $roleAdmin->permissions()->attach([$permCitaRead->id, $permCitaUpdate->id, $permCitaDelete->id]);

        // Asignar roles a usuarios
        BusinessUserRole::create([
            'user_id' => $this->staffBusiness1->id,
            'business_id' => $this->business1->id,
            'role_id' => $roleStaff->id,
        ]);

        BusinessUserRole::create([
            'user_id' => $this->adminBusiness1->id,
            'business_id' => $this->business1->id,
            'role_id' => $roleAdmin->id,
        ]);
    }

    /**
     * Test: Usuario final solo ve sus propias citas
     */
    public function test_user_can_only_see_own_appointments(): void
    {
        Sanctum::actingAs($this->userFinal);

        $response = $this->getJson('/api/v1/appointments');

        $response->assertOk();
        $data = $response->json('data');
        
        // Usuario tiene 2 citas en diferentes negocios
        $this->assertCount(2, $data);
    }

    /**
     * Test: Usuario final puede ver detalle de su cita
     */
    public function test_user_can_view_own_appointment_detail(): void
    {
        Sanctum::actingAs($this->userFinal);

        $response = $this->getJson("/api/v1/appointments/{$this->appointment1->id}");

        $response->assertOk();
        $response->assertJsonPath('data.id', $this->appointment1->id);
    }

    /**
     * Test: Usuario final NO puede ver cita de otro usuario
     */
    public function test_user_cannot_view_other_users_appointment(): void
    {
        $otherUser = User::factory()->create();
        $otherAppointment = Appointment::factory()->create([
            'business_id' => $this->business1->id,
            'user_id' => $otherUser->id,
            'employee_id' => $this->employee1->id,
            'service_id' => $this->service1->id,
        ]);

        Sanctum::actingAs($this->userFinal);

        $response = $this->getJson("/api/v1/appointments/{$otherAppointment->id}");

        $response->assertForbidden();
    }

    /**
     * Test: Staff del negocio puede ver citas del negocio
     */
    public function test_staff_can_view_business_appointments(): void
    {
        Sanctum::actingAs($this->staffBusiness1);

        $response = $this->getJson("/api/v1/appointments/{$this->appointment1->id}");

        $response->assertOk();
    }

    /**
     * Test: Staff NO puede ver citas de otro negocio
     * Nota: Global Scope filtra las citas, por lo que devuelve 404 (Not Found)
     * en lugar de 403 (Forbidden) - esto es el comportamiento deseado para
     * no revelar la existencia de recursos de otros tenants.
     */
    public function test_staff_cannot_view_other_business_appointments(): void
    {
        Sanctum::actingAs($this->staffBusiness1);

        $response = $this->getJson("/api/v1/appointments/{$this->appointment2->id}");

        // Global Scope hace que la cita no sea visible, devuelve 404
        $response->assertNotFound();
    }

    /**
     * Test: Staff puede actualizar estado de cita de su negocio
     */
    public function test_staff_can_update_business_appointment_status(): void
    {
        Sanctum::actingAs($this->staffBusiness1);

        $response = $this->patchJson("/api/v1/appointments/{$this->appointment1->id}", [
            'estado' => Appointment::ESTADO_COMPLETED,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('appointments', [
            'id' => $this->appointment1->id,
            'estado' => Appointment::ESTADO_COMPLETED,
        ]);
    }

    /**
     * Test: Usuario final puede cancelar su propia cita
     */
    public function test_user_can_cancel_own_appointment(): void
    {
        Sanctum::actingAs($this->userFinal);

        $response = $this->patchJson("/api/v1/appointments/{$this->appointment1->id}/cancel", [
            'motivo_cancelacion' => 'No puedo asistir',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('appointments', [
            'id' => $this->appointment1->id,
            'estado' => Appointment::ESTADO_CANCELLED,
        ]);
    }

    /**
     * Test: Usuario final NO puede actualizar estado (solo cancelar)
     */
    public function test_user_cannot_update_appointment_status(): void
    {
        Sanctum::actingAs($this->userFinal);

        $response = $this->patchJson("/api/v1/appointments/{$this->appointment1->id}", [
            'estado' => Appointment::ESTADO_COMPLETED,
        ]);

        $response->assertForbidden();
    }

    /**
     * Test: Staff NO puede actualizar cita de otro negocio
     * Nota: Global Scope filtra las citas, por lo que devuelve 404 (Not Found)
     * en lugar de 403 (Forbidden) - esto es el comportamiento deseado para
     * no revelar la existencia de recursos de otros tenants.
     */
    public function test_staff_cannot_update_other_business_appointment(): void
    {
        Sanctum::actingAs($this->staffBusiness1);

        $response = $this->patchJson("/api/v1/appointments/{$this->appointment2->id}", [
            'estado' => Appointment::ESTADO_COMPLETED,
        ]);

        // Global Scope hace que la cita no sea visible, devuelve 404
        $response->assertNotFound();
    }

    /**
     * Test: Notas internas solo visibles para staff
     */
    public function test_internal_notes_only_visible_to_staff(): void
    {
        // Agregar notas internas
        $this->appointment1->update(['notas_internas' => 'Nota secreta']);

        // Usuario final no debe ver notas internas
        Sanctum::actingAs($this->userFinal);
        $response = $this->getJson("/api/v1/appointments/{$this->appointment1->id}");
        $response->assertOk();
        $this->assertArrayNotHasKey('notas_internas', $response->json('data'));

        // Staff debe ver notas internas
        Sanctum::actingAs($this->staffBusiness1);
        $response = $this->getJson("/api/v1/appointments/{$this->appointment1->id}");
        $response->assertOk();
        $response->assertJsonPath('data.notas_internas', 'Nota secreta');
    }

    /**
     * Test: Crear cita requiere autenticación
     */
    public function test_create_appointment_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/appointments', [
            'business_id' => $this->business1->id,
            'employee_id' => $this->employee1->id,
            'service_id' => $this->service1->id,
            'fecha_hora_inicio' => Carbon::now()->addDays(3)->setHour(10)->format('Y-m-d H:i:s'),
        ]);

        $response->assertUnauthorized();
    }

    /**
     * Test: Global scope filtra citas por business_id del usuario
     */
    public function test_global_scope_filters_by_business_id(): void
    {
        // Usuario con current_business_id solo ve citas de ese negocio via global scope
        $this->staffBusiness1->current_business_id = $this->business1->id;
        $this->staffBusiness1->save();

        $this->actingAs($this->staffBusiness1);

        // El global scope debe filtrar automáticamente
        $appointments = Appointment::all();
        
        foreach ($appointments as $appointment) {
            $this->assertEquals($this->business1->id, $appointment->business_id);
        }
    }
}
