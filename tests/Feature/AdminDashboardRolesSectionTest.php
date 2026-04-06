<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessUserRole;
use App\Models\PlatformAdmin;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminDashboardRolesSectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_roles_section_displays_seeded_roles_and_permissions(): void
    {
        $this->actingAsPlatformAdminUser();
        $this->seed(RolesAndPermissionsSeeder::class);

        $response = $this->get(route('admin.dashboard', ['seccion' => 'roles']));

        $response->assertOk();
        $response->assertSee('Jerarquia de roles');
        $response->assertSee('PLATAFORMA_ADMIN');
        $response->assertSee('Ver asignados');
        $response->assertSee('permisos');
    }

    public function test_roles_section_supports_legacy_roles_schema_with_name_column(): void
    {
        $this->actingAsPlatformAdminUser();

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('business_user_roles');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('guard_name', 60)->default('web');
            $table->timestamps();
        });
        Schema::enableForeignKeyConstraints();

        DB::table('roles')->insert([
            'name' => 'PLATAFORMA_ADMIN',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->get(route('admin.dashboard', ['seccion' => 'roles']));

        $response->assertOk();
        $response->assertSee('Jerarquia de roles');
        $response->assertSee('PLATAFORMA_ADMIN');
        $response->assertDontSee('No se encontraron roles en el sistema.');
    }

    public function test_roles_section_embeds_assigned_users_for_drawer_actions(): void
    {
        $this->actingAsPlatformAdminUser();
        $this->seed(RolesAndPermissionsSeeder::class);

        $business = Business::factory()->approved()->createOne([
            'nombre' => 'Negocio Prueba',
        ]);

        /** @var User $assignedUser */
        $assignedUser = User::factory()->createOne([
            'nombre' => 'Usuario',
            'apellidos' => 'Asignado',
            'email' => 'asignado@example.test',
        ]);

        $staffRole = Role::query()->where('nombre', 'NEGOCIO_STAFF')->firstOrFail();

        BusinessUserRole::query()->withoutGlobalScopes()->create([
            'user_id' => $assignedUser->id,
            'business_id' => $business->id,
            'role_id' => $staffRole->id,
            'asignado_el' => now(),
        ]);

        $response = $this->get(route('admin.dashboard', ['seccion' => 'roles']));

        $response->assertOk();
        $response->assertSee('Ver asignados');
        $response->assertSee('asignado@example.test');
        $response->assertSee('Negocio Prueba');
    }

    public function test_platform_admin_can_update_role_assignment_from_roles_section(): void
    {
        $this->actingAsPlatformAdminUser();
        $this->seed(RolesAndPermissionsSeeder::class);

        $business = Business::factory()->approved()->createOne();
        $assignedUser = User::factory()->createOne();
        $staffRole = Role::query()->where('nombre', 'NEGOCIO_STAFF')->firstOrFail();
        $managerRole = Role::query()->where('nombre', 'NEGOCIO_MANAGER')->firstOrFail();

        $assignment = BusinessUserRole::query()->withoutGlobalScopes()->create([
            'user_id' => $assignedUser->id,
            'business_id' => $business->id,
            'role_id' => $staffRole->id,
            'asignado_el' => now(),
        ]);

        $response = $this->patchJson(route('admin.role-assignments.update', ['id' => $assignment->id]), [
            'role_id' => $managerRole->id,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.role_id', $managerRole->id);

        $this->assertDatabaseHas('business_user_roles', [
            'id' => $assignment->id,
            'role_id' => $managerRole->id,
        ]);
    }

    public function test_platform_admin_can_delete_role_assignment_from_roles_section(): void
    {
        $this->actingAsPlatformAdminUser();
        $this->seed(RolesAndPermissionsSeeder::class);

        $business = Business::factory()->approved()->createOne();
        $assignedUser = User::factory()->createOne();
        $staffRole = Role::query()->where('nombre', 'NEGOCIO_STAFF')->firstOrFail();

        $assignment = BusinessUserRole::query()->withoutGlobalScopes()->create([
            'user_id' => $assignedUser->id,
            'business_id' => $business->id,
            'role_id' => $staffRole->id,
            'asignado_el' => now(),
        ]);

        $response = $this->deleteJson(route('admin.role-assignments.destroy', ['id' => $assignment->id]));

        $response->assertOk();
        $this->assertSoftDeleted('business_user_roles', [
            'id' => $assignment->id,
        ]);
    }

    private function actingAsPlatformAdminUser(): User
    {
        /** @var User $user */
        $user = User::factory()->createOne([
            'email_verified_at' => now(),
        ]);

        PlatformAdmin::factory()->createOne([
            'email' => $user->email,
            'nombre' => $user->nombre,
            'activo' => true,
        ]);

        $this->actingAs($user);

        return $user;
    }
}
