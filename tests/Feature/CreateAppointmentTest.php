<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Business;
use App\Models\Service;
use App\Models\Employee;
use App\Models\BusinessLocation;
use App\Models\Role;
use App\Models\BusinessUserRole;
use App\Models\ScheduleTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Carbon\Carbon;
use Tests\TestCase;

class CreateAppointmentTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $business;
    protected $service;
    protected $employee;
    protected $location;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed roles first
        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\RolesAndPermissionsSeeder']);
        
        // Create user
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
        ]);
        
        // Create business
        $this->business = Business::factory()->create();
        
        // Get admin role
        $adminRole = Role::where('nombre', 'NEGOCIO_ADMIN')->first();
        
        BusinessUserRole::create([
            'user_id' => $this->user->id,
            'business_id' => $this->business->id,
            'role_id' => $adminRole->id,
        ]);
        
        $this->user->update(['current_business_id' => $this->business->id]);
        
        // Refresh user to get updated current_business_id
        $this->user->refresh();
        
        // Create location
        $this->location = BusinessLocation::factory()->create([
            'business_id' => $this->business->id,
            'activo' => true,
        ]);
        
        // Create schedules (Mon-Sat)
        for ($day = 1; $day <= 6; $day++) {
            ScheduleTemplate::create([
                'business_location_id' => $this->location->id,
                'dia_semana' => $day,
                'hora_apertura' => '09:00',
                'hora_cierre' => '18:00',
                'activo' => true,
            ]);
        }
        
        // Create service
        $this->service = Service::factory()->create([
            'business_id' => $this->business->id,
            'activo' => true,
        ]);
        
        // Create employee
        $this->employee = Employee::factory()->create([
            'business_id' => $this->business->id,
            'estado' => 'disponible',
        ]);
        
        $this->employee->services()->attach($this->service->id);
    }

    /** @test */
    public function it_can_render_create_appointment_component()
    {
        $this->actingAs($this->user);
        
        $response = $this->get(route('appointments.create'));
        
        $response->assertStatus(200);
        $response->assertSeeLivewire('appointments.create-appointment-form');
    }

    /** @test */
    public function it_loads_available_services_on_mount()
    {
        $this->actingAs($this->user);
        
        $component = Livewire::test(\App\Livewire\Appointments\CreateAppointment::class);
        
        $services = $component->get('services');
        // The service should be loaded based on user's current_business_id
        $this->assertTrue($services->contains('id', $this->service->id));
    }

    /** @test */
    public function it_loads_employees_when_service_is_selected()
    {
        $this->actingAs($this->user);
        
        // Use set() which triggers updated hooks in Livewire 3
        $component = Livewire::test(\App\Livewire\Appointments\CreateAppointment::class)
            ->set('serviceId', $this->service->id);
        
        $employees = $component->get('employees');
        $this->assertTrue($employees->contains('id', $this->employee->id));
    }

    /** @test */
    public function it_can_navigate_through_steps()
    {
        $this->actingAs($this->user);
        
        Livewire::test(\App\Livewire\Appointments\CreateAppointment::class)
            ->assertSet('currentStep', 1)
            ->set('serviceId', $this->service->id)
            ->call('nextStep')
            ->assertSet('currentStep', 2)
            ->call('previousStep')
            ->assertSet('currentStep', 1);
    }

    /** @test */
    public function it_validates_required_fields_on_each_step()
    {
        $this->actingAs($this->user);
        
        // Step 1: Service required
        Livewire::test(\App\Livewire\Appointments\CreateAppointment::class)
            ->call('nextStep')
            ->assertHasErrors(['serviceId']);
        
        // Step 2: Employee, slot required (selectedDate has default)
        Livewire::test(\App\Livewire\Appointments\CreateAppointment::class)
            ->set('serviceId', $this->service->id)
            ->call('nextStep')
            ->assertSet('currentStep', 2)
            ->set('selectedDate', null) // Remove default
            ->call('nextStep')
            ->assertHasErrors(['employeeId']);
    }
}
