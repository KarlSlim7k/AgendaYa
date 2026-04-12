<?php

namespace App\Livewire\Appointments;

use App\Livewire\Concerns\UsesBusinessLayout;
use App\Models\Appointment;
use App\Models\Employee;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AppointmentCalendar extends Component
{
    use UsesBusinessLayout;

    public $currentDate;
    public $viewMode = 'week'; // day, week, month
    public $selectedEmployee = '';
    public $appointmentsByDate = [];

    protected $queryString = [
        'currentDate' => ['except' => ''],
        'viewMode' => ['except' => 'week'],
        'selectedEmployee' => ['except' => ''],
    ];

    public function mount()
    {
        if (!$this->currentDate) {
            $this->currentDate = Carbon::now()->format('Y-m-d');
        }
    }

    public function goToToday()
    {
        $this->currentDate = Carbon::now()->format('Y-m-d');
    }

    public function goNext()
    {
        $date = Carbon::parse($this->currentDate);
        if ($this->viewMode === 'day') {
            $date->addDay();
        } elseif ($this->viewMode === 'week') {
            $date->addWeek();
        } else {
            $date->addMonth();
        }
        $this->currentDate = $date->format('Y-m-d');
    }

    public function goPrevious()
    {
        $date = Carbon::parse($this->currentDate);
        if ($this->viewMode === 'day') {
            $date->subDay();
        } elseif ($this->viewMode === 'week') {
            $date->subWeek();
        } else {
            $date->subMonth();
        }
        $this->currentDate = $date->format('Y-m-d');
    }

    public function loadAppointments()
    {
        $user = auth()->user();
        $date = Carbon::parse($this->currentDate);

        $query = Appointment::with(['user', 'service', 'employee'])
            ->where('business_id', $user->current_business_id);

        // Filter by employee if selected
        if ($this->selectedEmployee) {
            $query->where('employee_id', $this->selectedEmployee);
        }

        // Filter by date range based on view mode
        if ($this->viewMode === 'day') {
            $query->whereDate('fecha_hora_inicio', $date);
            $appointments = $query->orderBy('fecha_hora_inicio')->get();
            
            $this->appointmentsByDate = [
                $date->format('Y-m-d') => $appointments
            ];
        } elseif ($this->viewMode === 'week') {
            $startOfWeek = $date->copy()->startOfWeek();
            $endOfWeek = $date->copy()->endOfWeek();
            
            $query->whereBetween('fecha_hora_inicio', [$startOfWeek, $endOfWeek]);
            $appointments = $query->orderBy('fecha_hora_inicio')->get();
            
            // Group by date
            $this->appointmentsByDate = $appointments->groupBy(function($apt) {
                return Carbon::parse($apt->fecha_hora_inicio)->format('Y-m-d');
            });
        } else {
            $startOfMonth = $date->copy()->startOfMonth();
            $endOfMonth = $date->copy()->endOfMonth();
            
            $query->whereBetween('fecha_hora_inicio', [$startOfMonth, $endOfMonth]);
            $appointments = $query->orderBy('fecha_hora_inicio')->get();
            
            // Group by date
            $this->appointmentsByDate = $appointments->groupBy(function($apt) {
                return Carbon::parse($apt->fecha_hora_inicio)->format('Y-m-d');
            });
        }
    }

    public function render()
    {
        $this->loadAppointments();

        $employees = Employee::where('business_id', auth()->user()->current_business_id)
            ->whereNotIn('estado', ['baja'])
            ->orderBy('nombre')
            ->get();

        $date = Carbon::parse($this->currentDate);
        
        // Generate calendar data based on view mode
        $calendarData = $this->generateCalendarData($date);

        return $this->renderInBusinessLayout('livewire.appointments.appointment-calendar', [
            'employees' => $employees,
            'calendarData' => $calendarData,
            'date' => $date,
        ], 'Calendario de Citas', 'Principal');
    }

    private function generateCalendarData(Carbon $date)
    {
        if ($this->viewMode === 'day') {
            return $this->generateDayView($date);
        } elseif ($this->viewMode === 'week') {
            return $this->generateWeekView($date);
        } else {
            return $this->generateMonthView($date);
        }
    }

    private function generateDayView(Carbon $date)
    {
        $dateStr = $date->format('Y-m-d');
        $appointments = $this->appointmentsByDate[$dateStr] ?? collect([]);

        return [
            'type' => 'day',
            'date' => $date,
            'appointments' => $appointments,
            'hours' => collect(range(7, 20)), // 7 AM to 8 PM
        ];
    }

    private function generateWeekView(Carbon $date)
    {
        $startOfWeek = $date->copy()->startOfWeek();
        $days = collect([]);

        for ($i = 0; $i < 7; $i++) {
            $currentDay = $startOfWeek->copy()->addDays($i);
            $dateStr = $currentDay->format('Y-m-d');
            $appointments = $this->appointmentsByDate[$dateStr] ?? collect([]);
            
            $days->push([
                'date' => $currentDay,
                'dateStr' => $dateStr,
                'appointments' => $appointments,
                'isToday' => $currentDay->isToday(),
            ]);
        }

        return [
            'type' => 'week',
            'days' => $days,
            'startOfWeek' => $startOfWeek,
        ];
    }

    private function generateMonthView(Carbon $date)
    {
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();
        $startOfCalendar = $startOfMonth->copy()->startOfWeek();
        $endOfCalendar = $endOfMonth->copy()->endOfWeek();

        $days = collect([]);
        $currentDay = $startOfCalendar->copy();

        while ($currentDay <= $endOfCalendar) {
            $dateStr = $currentDay->format('Y-m-d');
            $appointments = $this->appointmentsByDate[$dateStr] ?? collect([]);
            
            $days->push([
                'date' => $currentDay->copy(),
                'dateStr' => $dateStr,
                'appointments' => $appointments,
                'isToday' => $currentDay->isToday(),
                'isCurrentMonth' => $currentDay->month == $date->month,
            ]);

            $currentDay->addDay();
        }

        return [
            'type' => 'month',
            'days' => $days,
            'month' => $date,
        ];
    }
}
