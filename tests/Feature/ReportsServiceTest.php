<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\Service;
use App\Models\Employee;
use App\Models\Appointment;
use App\Services\ReportsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class ReportsServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReportsService $reportsService;
    private Business $business;
    private BusinessLocation $location;
    private Service $service;
    private Employee $employee;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reportsService = new ReportsService();

        // Setup test data
        $this->business = Business::factory()->create();
        $this->location = BusinessLocation::factory()->create([
            'business_id' => $this->business->id,
        ]);
        $this->service = Service::factory()->create([
            'business_id' => $this->business->id,
            'precio' => 100.00,
        ]);
        $this->employee = Employee::factory()->create([
            'business_id' => $this->business->id,
        ]);
        $this->user = User::factory()->create();
    }

    public function test_get_appointment_metrics_calculates_correctly()
    {
        // Crear 2 citas hoy con diferente estado
        Appointment::factory()->create([
            'business_id' => $this->business->id,
            'service_id' => $this->service->id,
            'employee_id' => $this->employee->id,
            'user_id' => $this->user->id,
            'fecha_hora_inicio' => Carbon::today()->setHour(10),
            'fecha_hora_fin' => Carbon::today()->setHour(11),
            'estado' => 'confirmed',
        ]);
        Appointment::factory()->create([
            'business_id' => $this->business->id,
            'service_id' => $this->service->id,
            'employee_id' => $this->employee->id,
            'user_id' => $this->user->id,
            'fecha_hora_inicio' => Carbon::today()->setHour(14),
            'fecha_hora_fin' => Carbon::today()->setHour(15),
            'estado' => 'completed',
        ]);

        $metrics = $this->reportsService->getAppointmentMetrics($this->business);

        // Verificar que solo cuenta no-canceladas
        $this->assertGreaterThanOrEqual(1, $metrics['hoy']);
        $this->assertGreaterThanOrEqual(1, $metrics['semana']);
        $this->assertGreaterThanOrEqual(1, $metrics['mes']);
    }

    public function test_get_revenue_metrics_calculates_correctly()
    {
        // Crear 3 citas completadas este mes con precio 100
        for ($i = 0; $i < 3; $i++) {
            Appointment::factory()->create([
                'business_id' => $this->business->id,
                'service_id' => $this->service->id,
                'employee_id' => $this->employee->id,
                'user_id' => $this->user->id,
                'fecha_hora_inicio' => Carbon::now()->subDays($i),
                'fecha_hora_fin' => Carbon::now()->subDays($i)->addHour(),
                'estado' => 'completed',
            ]);
        }

        $metrics = $this->reportsService->getRevenueMetrics($this->business);

        $this->assertEquals(300.00, $metrics['mes']);
        $this->assertEquals(100.00, $metrics['promedio_cita']);
        $this->assertEquals(3, $metrics['citas_completadas']);
    }

    public function test_get_client_metrics_distinguishes_new_and_recurring()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Usuario 1: 2 citas (recurrente)
        Appointment::factory()->count(2)->create([
            'business_id' => $this->business->id,
            'service_id' => $this->service->id,
            'employee_id' => $this->employee->id,
            'user_id' => $user1->id,
            'fecha_hora_inicio' => Carbon::now()->subDays(5),
            'fecha_hora_fin' => Carbon::now()->subDays(5)->addHour(),
            'estado' => 'completed',
        ]);

        // Usuario 2: 1 cita (nuevo)
        Appointment::factory()->create([
            'business_id' => $this->business->id,
            'service_id' => $this->service->id,
            'employee_id' => $this->employee->id,
            'user_id' => $user2->id,
            'fecha_hora_inicio' => Carbon::now()->subDays(2),
            'fecha_hora_fin' => Carbon::now()->subDays(2)->addHour(),
            'estado' => 'completed',
        ]);

        $metrics = $this->reportsService->getClientMetrics($this->business);

        $this->assertEquals(2, $metrics['unicos_mes']);
        $this->assertEquals(2, $metrics['nuevos_mes']);
        $this->assertEquals(1, $metrics['recurrentes']);
    }

    public function test_get_operational_metrics_calculates_rates()
    {
        // Total: 10 citas
        // 2 canceladas (20%), 1 no-show (10%)
        Appointment::factory()->count(7)->create([
            'business_id' => $this->business->id,
            'service_id' => $this->service->id,
            'employee_id' => $this->employee->id,
            'user_id' => $this->user->id,
            'fecha_hora_inicio' => Carbon::now()->subDays(5),
            'fecha_hora_fin' => Carbon::now()->subDays(5)->addHour(),
            'estado' => 'completed',
        ]);
        Appointment::factory()->count(2)->create([
            'business_id' => $this->business->id,
            'service_id' => $this->service->id,
            'employee_id' => $this->employee->id,
            'user_id' => $this->user->id,
            'fecha_hora_inicio' => Carbon::now()->subDays(3),
            'fecha_hora_fin' => Carbon::now()->subDays(3)->addHour(),
            'estado' => 'cancelled',
        ]);
        Appointment::factory()->create([
            'business_id' => $this->business->id,
            'service_id' => $this->service->id,
            'employee_id' => $this->employee->id,
            'user_id' => $this->user->id,
            'fecha_hora_inicio' => Carbon::now()->subDays(1),
            'fecha_hora_fin' => Carbon::now()->subDays(1)->addHour(),
            'estado' => 'no_show',
        ]);

        $metrics = $this->reportsService->getOperationalMetrics($this->business);

        $this->assertEquals(20.0, $metrics['tasa_cancelacion']);
        $this->assertEquals(10.0, $metrics['tasa_no_show']);
        $this->assertGreaterThan(0, $metrics['tasa_ocupacion']);
    }

    public function test_get_top_services_returns_correctly()
    {
        $service2 = Service::factory()->create([
            'business_id' => $this->business->id,
            'precio' => 150.00,
        ]);

        // Servicio 1: 5 citas
        Appointment::factory()->count(5)->create([
            'business_id' => $this->business->id,
            'service_id' => $this->service->id,
            'employee_id' => $this->employee->id,
            'user_id' => $this->user->id,
            'fecha_hora_inicio' => Carbon::now()->subDays(5),
            'fecha_hora_fin' => Carbon::now()->subDays(5)->addHour(),
            'estado' => 'completed',
        ]);

        // Servicio 2: 3 citas
        Appointment::factory()->count(3)->create([
            'business_id' => $this->business->id,
            'service_id' => $service2->id,
            'employee_id' => $this->employee->id,
            'user_id' => $this->user->id,
            'fecha_hora_inicio' => Carbon::now()->subDays(3),
            'fecha_hora_fin' => Carbon::now()->subDays(3)->addHour(),
            'estado' => 'completed',
        ]);

        $topServices = $this->reportsService->getTopServices($this->business, 5);

        $this->assertCount(2, $topServices);
        $this->assertEquals($this->service->id, $topServices[0]['servicio_id']);
        $this->assertEquals(5, $topServices[0]['cantidad']);
        $this->assertEquals(500.00, $topServices[0]['ingresos']);
    }

    public function test_get_top_employee_returns_most_busy()
    {
        $employee2 = Employee::factory()->create([
            'business_id' => $this->business->id,
        ]);

        // Empleado 1: 5 citas
        Appointment::factory()->count(5)->create([
            'business_id' => $this->business->id,
            'service_id' => $this->service->id,
            'employee_id' => $this->employee->id,
            'user_id' => $this->user->id,
            'fecha_hora_inicio' => Carbon::now()->subDays(5),
            'fecha_hora_fin' => Carbon::now()->subDays(5)->addHour(),
            'estado' => 'completed',
        ]);

        // Empleado 2: 2 citas
        Appointment::factory()->count(2)->create([
            'business_id' => $this->business->id,
            'service_id' => $this->service->id,
            'employee_id' => $employee2->id,
            'user_id' => $this->user->id,
            'fecha_hora_inicio' => Carbon::now()->subDays(3),
            'fecha_hora_fin' => Carbon::now()->subDays(3)->addHour(),
            'estado' => 'completed',
        ]);

        $topEmployee = $this->reportsService->getTopEmployee($this->business);

        $this->assertEquals($this->employee->id, $topEmployee['id']);
        $this->assertEquals(5, $topEmployee['citas_mes']);
    }

    public function test_get_appointments_report_filters_correctly()
    {
        $service2 = Service::factory()->create([
            'business_id' => $this->business->id,
        ]);
        $employee2 = Employee::factory()->create([
            'business_id' => $this->business->id,
        ]);

        // Crear citas con diferentes combinaciones
        Appointment::factory()->create([
            'business_id' => $this->business->id,
            'service_id' => $this->service->id,
            'employee_id' => $this->employee->id,
            'user_id' => $this->user->id,
            'fecha_hora_inicio' => Carbon::now()->subDays(5),
            'fecha_hora_fin' => Carbon::now()->subDays(5)->addHour(),
            'estado' => 'completed',
        ]);
        Appointment::factory()->create([
            'business_id' => $this->business->id,
            'service_id' => $service2->id,
            'employee_id' => $employee2->id,
            'user_id' => $this->user->id,
            'fecha_hora_inicio' => Carbon::now()->subDays(2),
            'fecha_hora_fin' => Carbon::now()->subDays(2)->addHour(),
            'estado' => 'cancelled',
        ]);

        // Filtrar por servicio específico
        $report = $this->reportsService->getAppointmentsReport(
            $this->business,
            Carbon::now()->subWeek(),
            Carbon::now(),
            $this->service->id
        )->get();

        $this->assertCount(1, $report);

        // Filtrar por estado
        $report = $this->reportsService->getAppointmentsReport(
            $this->business,
            Carbon::now()->subWeek(),
            Carbon::now(),
            null,
            null,
            'cancelled'
        )->get();

        $this->assertCount(1, $report);
    }

    public function test_get_dashboard_metrics_aggregates_all_metrics()
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

        $dashboard = $this->reportsService->getDashboardMetrics($this->business);

        $this->assertArrayHasKey('citas', $dashboard);
        $this->assertArrayHasKey('ingresos', $dashboard);
        $this->assertArrayHasKey('clientes', $dashboard);
        $this->assertArrayHasKey('operacion', $dashboard);
        $this->assertArrayHasKey('top_servicios', $dashboard);
        $this->assertArrayHasKey('empleado_destacado', $dashboard);

        $this->assertIsArray($dashboard['citas']);
        $this->assertIsArray($dashboard['ingresos']);
        $this->assertIsArray($dashboard['clientes']);
        $this->assertIsArray($dashboard['operacion']);
    }

    public function test_get_appointments_chart_data_returns_30_days()
    {
        // Crear citas en diferentes días
        Appointment::factory()->create([
            'business_id' => $this->business->id,
            'service_id' => $this->service->id,
            'employee_id' => $this->employee->id,
            'user_id' => $this->user->id,
            'fecha_hora_inicio' => Carbon::now()->subDays(5)->setHour(10),
            'fecha_hora_fin' => Carbon::now()->subDays(5)->setHour(11),
            'estado' => 'completed',
        ]);
        Appointment::factory()->create([
            'business_id' => $this->business->id,
            'service_id' => $this->service->id,
            'employee_id' => $this->employee->id,
            'user_id' => $this->user->id,
            'fecha_hora_inicio' => Carbon::now()->subDays(10)->setHour(10),
            'fecha_hora_fin' => Carbon::now()->subDays(10)->setHour(11),
            'estado' => 'completed',
        ]);

        $chartData = $this->reportsService->getAppointmentsChartData($this->business);

        $this->assertArrayHasKey('labels', $chartData);
        $this->assertArrayHasKey('data', $chartData);
        $this->assertCount(30, $chartData['labels']);
        $this->assertCount(30, $chartData['data']);
        
        // Verificar que hay al menos algunos valores > 0
        $this->assertGreaterThan(0, array_sum($chartData['data']));
    }
}
