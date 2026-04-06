<?php

namespace App\Livewire\Appointments;

use App\Livewire\Concerns\UsesBusinessLayout;
use App\Models\BusinessLocation;
use App\Models\Employee;
use App\Models\Service;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Services\AppointmentService;
use Carbon\Carbon;
use Livewire\Component;

class CreateAppointmentForm extends Component
{
    use UsesBusinessLayout;

    public $currentStep = 1;
    
    // Step 1: Service selection
    public $selectedService = null;
    
    // Step 2: Employee selection
    public $selectedEmployee = null;
    
    // Step 3: Date and time
    public $selectedDate = null;
    public $selectedSlot = null;
    public $availableSlots = [];
    
    // Step 4: Customer info
    public $customerSearch = '';
    public $selectedCustomer = null;
    public $searchResults = [];
    
    // Step 5: Notes
    public $notasCliente = '';
    public $notasInternas = '';

    protected $rules = [
        'selectedService' => 'required|exists:services,id',
        'selectedEmployee' => 'required|exists:employees,id',
        'selectedDate' => 'required|date|after_or_equal:today',
        'selectedSlot' => 'required',
        'selectedCustomer' => 'required|exists:users,id',
    ];

    public function mount()
    {
        // Initialize with tomorrow's date
        $this->selectedDate = now()->addDay()->format('Y-m-d');
    }

    public function nextStep()
    {
        $this->validateStep();
        
        if ($this->currentStep < 5) {
            $this->currentStep++;
            
            // Load data for next step
            if ($this->currentStep === 3 && $this->selectedEmployee) {
                $this->loadAvailableSlots();
            }
        }
    }

    public function previousStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    private function validateStep()
    {
        switch ($this->currentStep) {
            case 1:
                $this->validate(['selectedService' => 'required|exists:services,id']);
                break;
            case 2:
                $this->validate(['selectedEmployee' => 'required|exists:employees,id']);
                break;
            case 3:
                $this->validate([
                    'selectedDate' => 'required|date|after_or_equal:today',
                    'selectedSlot' => 'required',
                ]);
                break;
            case 4:
                $this->validate(['selectedCustomer' => 'required|exists:users,id']);
                break;
        }
    }

    public function updatedSelectedDate()
    {
        $this->selectedSlot = null;
        if ($this->selectedEmployee && $this->selectedService) {
            $this->loadAvailableSlots();
        }
    }

    public function updatedCustomerSearch()
    {
        $this->searchCustomers();
    }

    public function loadAvailableSlots()
    {
        $location = BusinessLocation::where('business_id', auth()->user()->current_business_id)->first();
        
        if (!$location) {
            $this->availableSlots = [];
            return;
        }

        $availabilityService = app(AvailabilityService::class);
        
        $slots = $availabilityService->generateSlots(
            auth()->user()->current_business_id,
            $this->selectedService,
            $location->id,
            Carbon::parse($this->selectedDate),
            Carbon::parse($this->selectedDate),
            $this->selectedEmployee
        );

        $this->availableSlots = $slots->toArray();
    }

    public function searchCustomers()
    {
        if (strlen($this->customerSearch) < 2) {
            $this->searchResults = [];
            return;
        }

        $this->searchResults = User::where(function($q) {
            $q->where('nombre', 'like', '%' . $this->customerSearch . '%')
              ->orWhere('email', 'like', '%' . $this->customerSearch . '%');
        })
        ->whereDoesntHave('businessRoles')
        ->limit(10)
        ->get()
        ->toArray();
    }

    public function selectCustomer($userId)
    {
        $this->selectedCustomer = $userId;
        $customer = User::find($userId);
        $this->customerSearch = $customer->nombre . ' - ' . $customer->email;
        $this->searchResults = [];
    }

    public function createAppointment()
    {
        $this->validate();

        try {
            $appointmentService = app(AppointmentService::class);
            
            $appointment = $appointmentService->createAppointment([
                'business_id' => auth()->user()->current_business_id,
                'user_id' => $this->selectedCustomer,
                'service_id' => $this->selectedService,
                'employee_id' => $this->selectedEmployee,
                'fecha_hora_inicio' => $this->selectedSlot,
                'notas_cliente' => $this->notasCliente,
                'notas_internas' => $this->notasInternas,
            ]);

            session()->flash('message', 'Cita creada exitosamente');
            return redirect()->route('business.appointments.index');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error al crear la cita: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $services = Service::where('business_id', auth()->user()->current_business_id)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();
        $employees = $this->selectedService 
            ? Employee::whereHas('services', fn($q) => $q->where('services.id', $this->selectedService))
                ->where('estado', 'disponible')
                ->orderBy('nombre')
                ->get()
            : collect();

        return $this->renderInBusinessLayout('livewire.appointments.create-appointment-form', [
            'services' => $services,
            'employees' => $employees,
        ], 'Nueva Cita', 'Principal');
    }
}
