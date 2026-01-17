<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BasicSystemTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test que la página principal carga correctamente.
     */
    public function test_homepage_loads_successfully(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('CitasEmpresariales');
    }

    /**
     * Test que la página de login existe.
     */
    public function test_login_page_exists(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    /**
     * Test que la página de registro existe.
     */
    public function test_register_page_exists(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    /**
     * Test que la base de datos tiene las tablas correctas.
     */
    public function test_database_has_required_tables(): void
    {
        $tables = [
            'users',
            'businesses',
            'business_locations',
            'roles',
            'permissions',
            'role_permissions',
            'business_user_roles',
            'platform_admins',
            'platform_settings',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(
                \Schema::hasTable($table),
                "Table {$table} does not exist"
            );
        }
    }

    /**
     * Test que las tablas tienen las columnas críticas.
     */
    public function test_businesses_table_has_business_id(): void
    {
        $this->assertTrue(\Schema::hasColumn('business_locations', 'business_id'));
        $this->assertTrue(\Schema::hasColumn('business_user_roles', 'business_id'));
    }
}
