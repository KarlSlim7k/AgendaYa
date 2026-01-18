<?php

namespace App\Livewire\Appointments;

use App\Models\Service;
use App\Models\Employee;
use App\Models\User;
use App\Models\Appointment;
use App\Services\AvailabilityService;
use Livewire\Component;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CreateAppointment extends Component
{
    // Step tracking
    public $currentStep = 1;
    
    // Form fields
    public $serviceId;
    public $employeeId;
    public $selectedDate;
    public $selectedSlot;
    public $userEmail;
    public $userName;
    public $userPhone;
    public $notes;
    
    // Available data
    public $services = [];
    public $employees = [];
    public $availableSlots = [];
    
    // Selected models
    public $selectedService;
    public $selectedEmployee;
    
    // Loading states
    public $loadingEmployees = false;
    public $loadingSlots = false;
    
    // Success state
    public $showSuccess = false;
    public $createdAppointment;

    protected $rules = [
        'serviceId' => 'required|exists:services,id',
        'employeeId' => 'required|exists:employees,id',
        'selectedDate' => 'required|date|after_or_equal:today',
        'selectedSlot' => 'required',
        'userEmail' => 'required|email',
        'userName' => 'required|string|min:3',
        'userPhone' => 'nullable|string|max:20',
        'notes' => 'nullable|string|max:500',
    ];

    protected $messages = [
        'serviceId.required' => 'Debe seleccionar un servicio',
        'employeeId.required' => 'Debe seleccionar un empleado',
        'selectedDate.required' => 'Debe seleccionar una fecha',
        'selectedDate.after_or_equal' => 'La fecha debe ser hoy o posterior',
        'selectedSlot.required' => 'Debe seleccionar un horario',
        'userEmail.required' => 'El email es obligatorio',
        'userEmail.email' => 'Debe ser un email válido',
        'userName.required' => 'El nombre es obligatorio',
        'userName.min' => 'El nombre debe tener al menos 3 caracteres',
    ];

    public function mount()
    {
        $businessId = auth()->user()->current_business_id;
        $this->services = Service::where('business_id', $businessId)
            ->where('activo', true)
            ->get();
        
        // Set default date to tomorrow
        $this->selectedDate = Carbon::tomorrow()->format('Y-m-d');
    }

    public function updatedServiceId($value)
    {
        $this->selectedService = Service::find($value);
        $this->employeeId = null;
        $this->selectedEmployee = null;
        $this->selectedSlot = null;
        $this->availableSlots = [];
        
        if ($value) {
            $this->loadingEmployees = true;
            $businessId = auth()->user()->current_business_id;
            
            // Get employees that can perform this service
            $this->employees = Employee::where('business_id', $businessId)
                ->where('estado', 'disponible')
                ->whereHas('services', function($query) use ($value) {
                    $query->where('services.id', $value);
                })
                ->get();
            
            $this->loadingEmployees = false;
        } else {
            $this->employees = [];
        }
    }

    public function updatedEmployeeId($value)
    {
        $this->selectedEmployee = Employee::find($value);
        $this->selectedSlot = null;
        
        if ($value && $this->serviceId && $this->selectedDate) {
            $this->loadAvailableSlots();
        } else {
            $this->availableSlots = [];
        }
    }

    public function updatedSelectedDate($value)
    {
        $this->selectedSlot = null;
        
        if ($this->employeeId && $this->serviceId && $value) {
            $this->loadAvailableSlots();
        } else {
            $this->availableSlots = [];
        }
    }

    public function loadAvailableSlots()
    {
        if (!$this->serviceId || !$this->employeeId || !$this->selectedDate) {
            return;
        }

        $this->loadingSlots = true;
        
        try {
            $businessId = auth()->user()->current_business_id;
            $service = Service::find($this->serviceId);
            $employee = Employee::find($this->employeeId);
            $date = Carbon::parse($this->selectedDate);
            
            $availabilityService = app(AvailabilityService::class);
            
            // Get location (first active location of business)
            $location = \App\Models\BusinessLocation::where('business_id', $businessId)
                ->where('activo', true)
                ->first();
            
            if (!$location) {
                $this->availableSlots = [];
                $this->addError('selectedDate', 'No hay sucursales activas configuradas');
                return;
            }

            $slots = $availabilityService->generateSlots(
                $businessId,
                $location->id,
                $service->id,
                $employee->id,
                $date,
                $date
            );
            
            $this->availableSlots = $slots->map(function($slot) {
                return [
                    'time' => $slot['hora_inicio'],
                    'display' => Carbon::parse($slot['hora_inicio'])->format('H:i'),
                ];
            })->toArray();
            
        } catch (\Exception $e) {
            $this->availableSlots = [];
            $this->addError('selectedDate', 'Error al cargar disponibilidad: ' . $e->getMessage());
        } finally {
            $this->loadingSlots = false;
        }
    }

    public function nextStep()
    {
        if ($this->currentStep === 1) {
            $this->validate([
                'serviceId' => 'required|exists:services,id',
            ]);
        } elseif ($this->currentStep === 2) {
            $this->validate([
                'employeeId' => 'required|exists:employees,id',
                'selectedDate' => 'required|date|after_or_equal:today',
                'selectedSlot' => 'required',
            ]);
        }
        
        $this->currentStep++;
    }

    public function previousStep()
    {
        $this->currentStep--;
    }

    public function submit()
    {
        $this->validate();
        
        try {
            DB::beginTransaction();
            
            // Find or create user
            $user = User::firstOrCreate(
                ['email' => $this->userEmail],
                [
                    'nombre' => explode(' ', $this->userName)[0] ?? $this->userName,
                    'apellidos' => implode(' ', array_slice(explode(' ', $this->userName), 1)) ?: null,
                    'telefono' => $this->userPhone,
                    'password' => bcrypt(str()->random(16)),
                ]
            );
            
            $businessId = auth()->user()->current_business_id;
            $service = Service::find($this->serviceId);
            
            // Get location
            $location = \App\Models\BusinessLocation::where('business_id', $businessId)
                ->where('activo', true)
                ->first();
            
            // Parse datetime
            $startDateTime = Carbon::parse($this->selectedDate . ' ' . $this->selectedSlot);
            $endDateTime = $startDateTime->copy()->addMinutes($service->duracion_minutos);
            
            // Check slot availability with lock
            $existingAppointments = Appointment::where('business_id', $businessId)
                ->where('employee_id', $this->employeeId)
                ->where('estado', '!=', 'cancelled')
                ->whereBetween('fecha_hora_inicio', [
                    $startDateTime->copy()->subMinutes($service->buffer_pre_minutos + $service->duracion_minutos),
                    $endDateTime->copy()->addMinutes($service->buffer_post_minutos)
                ])
                ->lockForUpdate()
                ->exists();
            
            if ($existingAppointments) {
                throw new \Exception('El horario seleccionado ya no está disponible');
            }
            
            // Create appointment
            $appointment = Appointment::create([
                'business_id' => $businessId,
                'user_id' => $user->id,
                'service_id' => $this->serviceId,
                'employee_id' => $this->employeeId,
                'fecha_hora_inicio' => $startDateTime,
                'fecha_hora_fin' => $endDateTime,
                'estado' => 'pending',
                'codigo_confirmacion' => strtoupper(substr(md5(uniqid()), 0, 8)),
                'notas_internas' => $this->notes,
            ]);
            
            DB::commit();
            
            $this->createdAppointment = $appointment->load(['user', 'service', 'employee']);
            $this->showSuccess = true;
            
            // Clear cache
            cache()->forget("slots:{$businessId}:{$location->id}:{$this->serviceId}:{$this->selectedDate}");
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->addError('submit', 'Error al crear la cita: ' . $e->getMessage());
        }
    }

    public function createAnother()
    {
        $this->reset([
            'currentStep', 'serviceId', 'employeeId', 'selectedDate', 
            'selectedSlot', 'userEmail', 'userName', 'userPhone', 'notes',
            'selectedService', 'selectedEmployee', 'availableSlots',
            'showSuccess', 'createdAppointment'
        ]);
        
        $this->mount();
    }

    public function render()
    {
        return view('livewire.appointments.create-appointment');
    }
}
