<?php

namespace App\Livewire\Schedule;

use App\Models\BusinessLocation;
use App\Models\ScheduleTemplate;
use App\Models\ScheduleException;
use Livewire\Component;
use Carbon\Carbon;

class ScheduleManagement extends Component
{
    public $locations = [];
    public $selectedLocationId;
    public $schedules = [];
    public $exceptions = [];
    
    // Schedule form
    public $editingScheduleId;
    public $scheduleDiaSemana;
    public $scheduleHoraApertura = '09:00';
    public $scheduleHoraCierre = '18:00';
    public $scheduleActivo = true;
    
    // Exception form
    public $showExceptionModal = false;
    public $editingExceptionId;
    public $exceptionTipo = 'feriado';
    public $exceptionFecha;
    public $exceptionFechaInicio;
    public $exceptionFechaFin;
    public $exceptionTodoElDia = true;
    public $exceptionHoraInicio;
    public $exceptionHoraFin;
    public $exceptionMotivo;

    protected $rules = [
        'scheduleHoraApertura' => 'required|date_format:H:i',
        'scheduleHoraCierre' => 'required|date_format:H:i|after:scheduleHoraApertura',
        'exceptionTipo' => 'required|in:feriado,vacaciones,cierre',
        'exceptionMotivo' => 'required|string|max:255',
    ];

    public function mount()
    {
        $businessId = auth()->user()->current_business_id;
        $this->locations = BusinessLocation::where('business_id', $businessId)
            ->where('activo', true)
            ->get();
        
        if ($this->locations->isNotEmpty()) {
            $this->selectedLocationId = $this->locations->first()->id;
            $this->loadSchedules();
            $this->loadExceptions();
        }
    }

    public function updatedSelectedLocationId()
    {
        $this->loadSchedules();
        $this->loadExceptions();
    }

    public function loadSchedules()
    {
        if (!$this->selectedLocationId) return;
        
        $existing = ScheduleTemplate::where('business_location_id', $this->selectedLocationId)
            ->get()
            ->keyBy('dia_semana');
        
        $days = [
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            0 => 'Domingo',
        ];
        
        $this->schedules = collect($days)->map(function($name, $day) use ($existing) {
            $schedule = $existing->get($day);
            return [
                'id' => $schedule->id ?? null,
                'dia_semana' => $day,
                'nombre' => $name,
                'hora_apertura' => $schedule->hora_apertura ?? '09:00',
                'hora_cierre' => $schedule->hora_cierre ?? '18:00',
                'activo' => $schedule->activo ?? false,
            ];
        })->values()->toArray();
    }

    public function saveSchedule($day)
    {
        $schedule = collect($this->schedules)->firstWhere('dia_semana', $day);
        
        ScheduleTemplate::updateOrCreate(
            [
                'business_location_id' => $this->selectedLocationId,
                'dia_semana' => $day,
            ],
            [
                'hora_apertura' => $schedule['hora_apertura'],
                'hora_cierre' => $schedule['hora_cierre'],
                'activo' => $schedule['activo'],
            ]
        );
        
        session()->flash('message', 'Horario actualizado correctamente');
        $this->loadSchedules();
    }

    public function toggleDay($day)
    {
        $index = collect($this->schedules)->search(fn($s) => $s['dia_semana'] == $day);
        if ($index !== false) {
            $this->schedules[$index]['activo'] = !$this->schedules[$index]['activo'];
            $this->saveSchedule($day);
        }
    }

    public function loadExceptions()
    {
        if (!$this->selectedLocationId) return;
        
        $this->exceptions = ScheduleException::where('business_location_id', $this->selectedLocationId)
            ->orderBy('fecha_inicio', 'desc')
            ->limit(20)
            ->get()
            ->toArray();
    }

    public function openExceptionModal()
    {
        $this->reset(['editingExceptionId', 'exceptionTipo', 'exceptionFecha', 'exceptionFechaInicio', 
                      'exceptionFechaFin', 'exceptionTodoElDia', 'exceptionHoraInicio', 
                      'exceptionHoraFin', 'exceptionMotivo']);
        $this->exceptionTipo = 'feriado';
        $this->exceptionTodoElDia = true;
        $this->showExceptionModal = true;
    }

    public function saveException()
    {
        $this->validate([
            'exceptionTipo' => 'required|in:feriado,vacaciones,cierre',
            'exceptionMotivo' => 'required|string|max:255',
        ]);
        
        if ($this->exceptionTipo === 'feriado') {
            $this->validate(['exceptionFecha' => 'required|date']);
            $fechaInicio = $this->exceptionFecha;
            $fechaFin = $this->exceptionFecha;
        } else {
            $this->validate([
                'exceptionFechaInicio' => 'required|date',
                'exceptionFechaFin' => 'required|date|after_or_equal:exceptionFechaInicio',
            ]);
            $fechaInicio = $this->exceptionFechaInicio;
            $fechaFin = $this->exceptionFechaFin;
        }
        
        ScheduleException::create([
            'business_location_id' => $this->selectedLocationId,
            'tipo' => $this->exceptionTipo,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'todo_el_dia' => $this->exceptionTodoElDia,
            'hora_inicio' => !$this->exceptionTodoElDia ? $this->exceptionHoraInicio : null,
            'hora_fin' => !$this->exceptionTodoElDia ? $this->exceptionHoraFin : null,
            'motivo' => $this->exceptionMotivo,
        ]);
        
        $this->showExceptionModal = false;
        $this->loadExceptions();
        session()->flash('message', 'Excepción creada correctamente');
    }

    public function deleteException($id)
    {
        ScheduleException::find($id)->delete();
        $this->loadExceptions();
        session()->flash('message', 'Excepción eliminada correctamente');
    }

    public function render()
    {
        return view('livewire.schedule.schedule-management');
    }
}
