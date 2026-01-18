<?php

namespace App\Livewire\Dashboard;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class BusinessDashboard extends Component
{
    public $selectedPeriod = 'today';
    public $startDate;
    public $endDate;
    
    // KPIs
    public $totalAppointments = 0;
    public $confirmedAppointments = 0;
    public $completedAppointments = 0;
    public $cancelledAppointments = 0;
    public $revenue = 0;
    public $uniqueClients = 0;
    
    // Charts data
    public $appointmentsByStatus = [];
    public $topServices = [];
    public $employeePerformance = [];
    public $upcomingAppointments = [];

    public function mount()
    {
        $this->updatePeriod();
    }

    public function updatedSelectedPeriod()
    {
        $this->updatePeriod();
    }

    private function updatePeriod()
    {
        $now = Carbon::now();
        
        switch ($this->selectedPeriod) {
            case 'today':
                $this->startDate = $now->copy()->startOfDay();
                $this->endDate = $now->copy()->endOfDay();
                break;
            case 'week':
                $this->startDate = $now->copy()->startOfWeek();
                $this->endDate = $now->copy()->endOfWeek();
                break;
            case 'month':
                $this->startDate = $now->copy()->startOfMonth();
                $this->endDate = $now->copy()->endOfMonth();
                break;
            case 'year':
                $this->startDate = $now->copy()->startOfYear();
                $this->endDate = $now->copy()->endOfYear();
                break;
        }
        
        $this->loadKPIs();
        $this->loadChartData();
        $this->loadUpcomingAppointments();
    }

    private function loadKPIs()
    {
        $businessId = auth()->user()->current_business_id;
        
        $appointments = Appointment::where('business_id', $businessId)
            ->whereBetween('fecha_hora_inicio', [$this->startDate, $this->endDate])
            ->with('service')
            ->get();
        
        $this->totalAppointments = $appointments->count();
        $this->confirmedAppointments = $appointments->where('estado', 'confirmed')->count();
        $this->completedAppointments = $appointments->where('estado', 'completed')->count();
        $this->cancelledAppointments = $appointments->where('estado', 'cancelled')->count();
        
        $this->revenue = $appointments->where('estado', 'completed')
            ->sum(fn($apt) => $apt->service->precio);
        
        $this->uniqueClients = $appointments->unique('user_id')->count();
    }

    private function loadChartData()
    {
        $businessId = auth()->user()->current_business_id;
        
        // Appointments by status
        $this->appointmentsByStatus = Appointment::where('business_id', $businessId)
            ->whereBetween('fecha_hora_inicio', [$this->startDate, $this->endDate])
            ->select('estado', DB::raw('count(*) as total'))
            ->groupBy('estado')
            ->get()
            ->pluck('total', 'estado')
            ->toArray();
        
        // Top services
        $this->topServices = Appointment::where('business_id', $businessId)
            ->whereBetween('fecha_hora_inicio', [$this->startDate, $this->endDate])
            ->select('service_id', DB::raw('count(*) as total'))
            ->with('service:id,nombre')
            ->groupBy('service_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn($apt) => [
                'nombre' => $apt->service->nombre,
                'total' => $apt->total
            ])
            ->toArray();
        
        // Employee performance
        $this->employeePerformance = Appointment::where('business_id', $businessId)
            ->whereBetween('fecha_hora_inicio', [$this->startDate, $this->endDate])
            ->where('estado', 'completed')
            ->select('employee_id', DB::raw('count(*) as total'))
            ->with('employee:id,nombre')
            ->groupBy('employee_id')
            ->orderByDesc('total')
            ->get()
            ->map(fn($apt) => [
                'nombre' => $apt->employee->nombre,
                'total' => $apt->total
            ])
            ->toArray();
    }

    private function loadUpcomingAppointments()
    {
        $businessId = auth()->user()->current_business_id;
        
        $this->upcomingAppointments = Appointment::where('business_id', $businessId)
            ->where('estado', 'confirmed')
            ->where('fecha_hora_inicio', '>=', Carbon::now())
            ->orderBy('fecha_hora_inicio')
            ->limit(10)
            ->with(['user', 'service', 'employee'])
            ->get()
            ->toArray();
    }

    public function render()
    {
        return view('livewire.dashboard.business-dashboard');
    }
}
