<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\BusinessUserRole;
use App\Models\Role;
use App\Models\Service;
use App\Models\Employee;
use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Carbon\Carbon;

class ReportsApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Business $business;
    private BusinessLocation $location;
    private Service $service;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles y permisos
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->user = User::factory()->create([
            'current_business_id' => null, // Inicialmente sin negocio
        ]);
        $this->business = Business::factory()->create();
        $this->location = BusinessLocation::factory()->create([
            'business_id' => $this->business->id,
        ]);

        // Asignar rol NEGOCIO_ADMIN
        $role = Role::where('nombre', 'NEGOCIO_ADMIN')->first();
        BusinessUserRole::create([
            'user_id' => $this->user->id,
            'business_id' => $this->business->id,
            'role_id' => $role->id,
        ]);

        // IMPORTANTE: Asignar negocio actual ANTES de autenticar con Sanctum
        $this->user->update(['current_business_id' => $this->business->id]);

        $this->service = Service::factory()->create([
            'business_id' => $this->business->id,
            'precio' => 100.00,
        ]);
        $this->employee = Employee::factory()->create([
            'business_id' => $this->business->id,
        ]);

        // Sanctum authentication DESPUÉS de asignar current_business_id
        Sanctum::actingAs($this->user);
    }

    public function test_get_dashboard_metrics_requires_authentication()
    {
        Sanctum::actingAs(User::factory()->create([
            'current_business_id' => null,
        ]));

        $response = $this->getJson('/api/v1/business/dashboard');

        $response->assertStatus(404)
            ->assertJson(['message' => 'No se encontró negocio activo']);
    }

    public function test_get_dashboard_metrics_returns_structure()
    {
        // Crear cita de prueba
        Appointment::factory()->create([
            'business_id' => $this->business->id,
            'service_id' => $this->service->id,
            'employee_id' => $this->employee->id,
            'user_id' => $this->user->id,
            'fecha_hora_inicio' => Carbon::today()->setHour(10),
            'fecha_hora_fin' => Carbon::today()->setHour(11),
            'estado' => 'completed',
        ]);

        $response = $this->getJson('/api/v1/business/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'citas' => ['hoy', 'semana', 'mes'],
                    'ingresos' => ['mes', 'promedio_cita', 'citas_completadas'],
                    'clientes' => ['unicos_mes', 'nuevos_mes', 'recurrentes'],
                    'operacion' => ['tasa_ocupacion', 'tasa_cancelacion', 'tasa_no_show'],
                    'top_servicios',
                    'empleado_destacado',
                ]
            ]);
    }

    public function test_get_appointments_report_requires_dates()
    {
        $response = $this->getJson('/api/v1/business/reports/appointments');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['fecha_inicio', 'fecha_fin']);
    }

    public function test_get_appointments_report_validates_date_format()
    {
        $response = $this->getJson('/api/v1/business/reports/appointments?fecha_inicio=invalid&fecha_fin=invalid');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['fecha_inicio', 'fecha_fin']);
    }

    public function test_get_appointments_report_returns_filtered_data()
    {
        $user = User::factory()->create();

        // Crear 3 citas en el rango
        Appointment::factory()->count(3)->create([
            'business_id' => $this->business->id,
            'service_id' => $this->service->id,
            'employee_id' => $this->employee->id,
            'user_id' => $user->id,
            'fecha_hora_inicio' => Carbon::now()->subDays(5)->setHour(10),
            'fecha_hora_fin' => Carbon::now()->subDays(5)->setHour(11),
            'estado' => 'completed',
        ]);

        // 1 cita fuera del rango
        Appointment::factory()->create([
            'business_id' => $this->business->id,
            'service_id' => $this->service->id,
            'employee_id' => $this->employee->id,
            'user_id' => $user->id,
            'fecha_hora_inicio' => Carbon::now()->subMonths(2),
            'fecha_hora_fin' => Carbon::now()->subMonths(2)->addHour(),
            'estado' => 'completed',
        ]);

        $response = $this->getJson('/api/v1/business/reports/appointments?' . http_build_query([
            'fecha_inicio' => Carbon::now()->subWeek()->format('Y-m-d'),
            'fecha_fin' => Carbon::now()->format('Y-m-d'),
        ]));

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'fecha_hora_inicio',
                        'fecha_hora_fin',
                        'estado',
                        'cliente' => ['id', 'nombre', 'email'],
                        'servicio' => ['id', 'nombre', 'precio'],
                        'empleado' => ['id', 'nombre'],
                    ]
                ]
            ]);
    }

    public function test_get_appointments_report_filters_by_service()
    {
        $user = User::factory()->create();
        $service2 = Service::factory()->create([
            'business_id' => $this->business->id,
        ]);

        // Servicio 1: 2 citas
        Appointment::factory()->count(2)->create([
            'business_id' => $this->business->id,
            'service_id' => $this->service->id,
            'employee_id' => $this->employee->id,
            'user_id' => $user->id,
            'fecha_hora_inicio' => Carbon::now()->subDays(3),
            'fecha_hora_fin' => Carbon::now()->subDays(3)->addHour(),
            'estado' => 'completed',
        ]);

        // Servicio 2: 1 cita
        Appointment::factory()->create([
            'business_id' => $this->business->id,
            'service_id' => $service2->id,
            'employee_id' => $this->employee->id,
            'user_id' => $user->id,
            'fecha_hora_inicio' => Carbon::now()->subDays(2),
            'fecha_hora_fin' => Carbon::now()->subDays(2)->addHour(),
            'estado' => 'completed',
        ]);

        $response = $this->getJson('/api/v1/business/reports/appointments?' . http_build_query([
            'fecha_inicio' => Carbon::now()->subWeek()->format('Y-m-d'),
            'fecha_fin' => Carbon::now()->format('Y-m-d'),
            'service_id' => $this->service->id,
        ]));

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_get_appointments_report_filters_by_employee()
    {
        $user = User::factory()->create();
        $employee2 = Employee::factory()->create([
            'business_id' => $this->business->id,
        ]);

        // Empleado 1: 2 citas
        Appointment::factory()->count(2)->create([
            'business_id' => $this->business->id,
            'service_id' => $this->service->id,
            'employee_id' => $this->employee->id,
            'user_id' => $user->id,
            'fecha_hora_inicio' => Carbon::now()->subDays(3),
            'fecha_hora_fin' => Carbon::now()->subDays(3)->addHour(),
            'estado' => 'completed',
        ]);

        // Empleado 2: 1 cita
        Appointment::factory()->create([
            'business_id' => $this->business->id,
            'service_id' => $this->service->id,
            'employee_id' => $employee2->id,
            'user_id' => $user->id,
            'fecha_hora_inicio' => Carbon::now()->subDays(2),
            'fecha_hora_fin' => Carbon::now()->subDays(2)->addHour(),
            'estado' => 'completed',
        ]);

        $response = $this->getJson('/api/v1/business/reports/appointments?' . http_build_query([
            'fecha_inicio' => Carbon::now()->subWeek()->format('Y-m-d'),
            'fecha_fin' => Carbon::now()->format('Y-m-d'),
            'employee_id' => $this->employee->id,
        ]));

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_get_appointments_report_filters_by_estado()
    {
        $user = User::factory()->create();

        // 2 completadas
        Appointment::factory()->count(2)->create([
            'business_id' => $this->business->id,
            'service_id' => $this->service->id,
            'employee_id' => $this->employee->id,
            'user_id' => $user->id,
            'fecha_hora_inicio' => Carbon::now()->subDays(3),
            'fecha_hora_fin' => Carbon::now()->subDays(3)->addHour(),
            'estado' => 'completed',
        ]);

        // 1 cancelada
        Appointment::factory()->create([
            'business_id' => $this->business->id,
            'service_id' => $this->service->id,
            'employee_id' => $this->employee->id,
            'user_id' => $user->id,
            'fecha_hora_inicio' => Carbon::now()->subDays(2),
            'fecha_hora_fin' => Carbon::now()->subDays(2)->addHour(),
            'estado' => 'cancelled',
        ]);

        $response = $this->getJson('/api/v1/business/reports/appointments?' . http_build_query([
            'fecha_inicio' => Carbon::now()->subWeek()->format('Y-m-d'),
            'fecha_fin' => Carbon::now()->format('Y-m-d'),
            'estado' => 'completed',
        ]));

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_get_top_services_returns_list()
    {
        $user = User::factory()->create();

        // Crear citas
        Appointment::factory()->count(3)->create([
            'business_id' => $this->business->id,
            'service_id' => $this->service->id,
            'employee_id' => $this->employee->id,
            'user_id' => $user->id,
            'fecha_hora_inicio' => Carbon::now()->subDays(5),
            'fecha_hora_fin' => Carbon::now()->subDays(5)->addHour(),
            'estado' => 'completed',
        ]);

        $response = $this->getJson('/api/v1/business/reports/services');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'servicio_id',
                        'nombre',
                        'cantidad',
                        'ingresos'
                    ]
                ]
            ]);
    }

    public function test_get_top_services_limits_results()
    {
        $user = User::factory()->create();

        // Crear 10 servicios con citas
        for ($i = 0; $i < 10; $i++) {
            $service = Service::factory()->create([
                'business_id' => $this->business->id,
            ]);
            Appointment::factory()->create([
                'business_id' => $this->business->id,
                'service_id' => $service->id,
                'employee_id' => $this->employee->id,
                'user_id' => $user->id,
                'fecha_hora_inicio' => Carbon::now()->subDays($i),
                'fecha_hora_fin' => Carbon::now()->subDays($i)->addHour(),
                'estado' => 'completed',
            ]);
        }

        $response = $this->getJson('/api/v1/business/reports/services?limit=3');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_get_chart_data_returns_30_days()
    {
        $user = User::factory()->create();

        // Crear citas en diferentes días
        Appointment::factory()->create([
            'business_id' => $this->business->id,
            'service_id' => $this->service->id,
            'employee_id' => $this->employee->id,
            'user_id' => $user->id,
            'fecha_hora_inicio' => Carbon::now()->subDays(5),
            'fecha_hora_fin' => Carbon::now()->subDays(5)->addHour(),
            'estado' => 'completed',
        ]);

        $response = $this->getJson('/api/v1/business/reports/chart-data');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'labels',
                    'data'
                ]
            ])
            ->assertJsonCount(30, 'data.labels')
            ->assertJsonCount(30, 'data.data');
    }
}
