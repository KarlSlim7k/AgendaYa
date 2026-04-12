<?php

namespace App\Livewire\PublicBooking;

use App\Models\Appointment;
use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\Employee;
use App\Models\ScheduleException;
use App\Models\ScheduleTemplate;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class BookingPage extends Component
{
    public $businessSlug;
    public $business;
    public $step = 1; // 1: service, 2: location, 3: employee, 4: datetime, 5: details, 6: confirmation
    
    // Step selections
    public $selectedService;
    public $selectedLocation;
    public $selectedEmployee;
    public $selectedDate;
    public $selectedTime;
    
    // Customer details
    public $customerName;
    public $customerEmail;
    public $customerPhone;
    public $customerNotes;
    
    // Available data
    public $services;
    public $locations;
    public $employees;
    public $availableTimes = [];
    
    protected $rules = [
        'selectedService' => 'required|exists:services,id',
        'selectedLocation' => 'required|exists:business_locations,id',
        'selectedEmployee' => 'required|exists:employees,id',
        'selectedDate' => 'required|date|after_or_equal:today',
        'selectedTime' => 'required',
        'customerName' => 'required|string|max:100',
        'customerEmail' => 'required|email|max:100',
        'customerPhone' => 'required|string|max:20',
        'customerNotes' => 'nullable|string|max:500',
    ];

    public function mount($businessSlug)
    {
        $this->businessSlug = $businessSlug;
        $this->business = Business::where('estado', 'approved')
            ->with('locations')
            ->firstOrFail();
        
        $this->services = Service::where('business_id', $this->business->id)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();
        
        $this->locations = BusinessLocation::where('business_id', $this->business->id)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();
    }

    public function selectService($serviceId)
    {
        $this->selectedService = $serviceId;
        $this->step = 2;
    }

    public function selectLocation($locationId)
    {
        $this->selectedLocation = $locationId;
        $this->step = 3;
        $this->loadEmployees();
    }

    public function loadEmployees()
    {
        $this->employees = Employee::where('business_id', $this->business->id)
            ->whereNotIn('estado', ['baja'])
            ->whereHas('services', function($q) {
                $q->where('service_id', $this->selectedService);
            })
            ->orderBy('nombre')
            ->get();
    }

    public function selectEmployee($employeeId)
    {
        $this->selectedEmployee = $employeeId;
        $this->step = 4;
        $this->selectedDate = null;
        $this->selectedTime = null;
    }

    public function selectDate($date)
    {
        $this->selectedDate = $date;
        $this->loadAvailableTimes();
    }

    public function loadAvailableTimes()
    {
        if (!$this->selectedDate || !$this->selectedEmployee || !$this->selectedLocation) {
            $this->availableTimes = [];
            return;
        }

        $service = Service::find($this->selectedService);
        $date = Carbon::parse($this->selectedDate);
        
        // Check if it's a holiday/exception
        $isException = ScheduleException::where('business_location_id', $this->selectedLocation)
            ->whereDate('fecha', $date)
            ->where('tipo', 'cerrado')
            ->exists();
        
        if ($isException) {
            $this->availableTimes = [];
            return;
        }

        // Get schedule template for this day
        $dayOfWeek = $date->dayOfWeek;
        $template = ScheduleTemplate::where('business_location_id', $this->selectedLocation)
            ->where('dia_semana', $dayOfWeek)
            ->where('activo', true)
            ->first();
        
        if (!$template) {
            $this->availableTimes = [];
            return;
        }

        // Generate time slots
        $start = Carbon::parse($this->selectedDate . ' ' . $template->hora_apertura);
        $end = Carbon::parse($this->selectedDate . ' ' . $template->hora_cierre);
        $duration = $service->duracion_minutos;
        
        $slots = [];
        $current = $start->copy();
        
        while ($current->copy()->addMinutes($duration)->lte($end)) {
            // Check if slot is already booked
            $isBooked = Appointment::where('employee_id', $this->selectedEmployee)
                ->whereDate('fecha_hora_inicio', $current)
                ->whereNotIn('estado', ['cancelled'])
                ->exists();
            
            if (!$isBooked && $current->gt(Carbon::now())) {
                $slots[] = $current->format('H:i');
            }
            
            $current->addMinutes($duration);
        }
        
        $this->availableTimes = $slots;
    }

    public function selectTime($time)
    {
        $this->selectedTime = $time;
        $this->step = 5;
    }

    public function bookAppointment()
    {
        $this->validate();

        // Create or find customer
        $customer = User::firstOrCreate(
            ['email' => $this->customerEmail],
            [
                'nombre' => $this->customerName,
                'telefono' => $this->customerPhone,
                'password' => Hash::make(uniqid()),
            ]
        );

        // Create appointment
        $startTime = Carbon::parse($this->selectedDate . ' ' . $this->selectedTime);
        $service = Service::find($this->selectedService);
        $endTime = $startTime->copy()->addMinutes($service->duracion_minutos);

        $appointment = Appointment::create([
            'business_id' => $this->business->id,
            'user_id' => $customer->id,
            'employee_id' => $this->selectedEmployee,
            'service_id' => $this->selectedService,
            'fecha_hora_inicio' => $startTime,
            'fecha_hora_fin' => $endTime,
            'estado' => 'pending',
            'notas_cliente' => $this->customerNotes,
        ]);

        $this->step = 6;
    }

    public function resetBooking()
    {
        $this->step = 1;
        $this->selectedService = null;
        $this->selectedLocation = null;
        $this->selectedEmployee = null;
        $this->selectedDate = null;
        $this->selectedTime = null;
        $this->customerName = '';
        $this->customerEmail = '';
        $this->customerPhone = '';
        $this->customerNotes = '';
    }

    public function render()
    {
        $selectedServiceData = $this->selectedService ? Service::find($this->selectedService) : null;
        
        return view('livewire.public-booking.booking-page', [
            'selectedServiceData' => $selectedServiceData,
        ])->layout('layouts.public', ['title' => 'Reservar Cita - ' . $this->business->nombre]);
    }
}
