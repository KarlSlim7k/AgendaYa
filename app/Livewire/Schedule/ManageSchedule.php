<?php

namespace App\Livewire\Schedule;

use App\Models\BusinessLocation;
use App\Models\ScheduleTemplate;
use Livewire\Component;

class ManageSchedule extends Component
{
    public $locations = [];
    public $selectedLocationId = null;
    public $templates = [];
    public $dias = [
        0 => 'Domingo',
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
    ];

    public function mount()
    {
        $this->locations = BusinessLocation::orderBy('nombre')->get();
        
        if ($this->locations->count() > 0) {
            $this->selectedLocationId = $this->locations->first()->id;
            $this->loadTemplates();
        }
    }

    public function updatedSelectedLocationId()
    {
        $this->loadTemplates();
    }

    public function loadTemplates()
    {
        if (!$this->selectedLocationId) {
            $this->templates = [];
            return;
        }

        $existing = ScheduleTemplate::where('location_id', $this->selectedLocationId)
            ->get()
            ->keyBy('dia_semana');

        // Inicializar todos los días
        $this->templates = [];
        for ($i = 0; $i <= 6; $i++) {
            $this->templates[$i] = [
                'id' => $existing->get($i)?->id,
                'dia_semana' => $i,
                'hora_apertura' => $existing->get($i)?->hora_apertura ?? '09:00',
                'hora_cierre' => $existing->get($i)?->hora_cierre ?? '18:00',
                'activo' => $existing->get($i)?->activo ?? ($i >= 1 && $i <= 5), // Lun-Vie activos por defecto
            ];
        }
    }

    public function toggleActivo($dia)
    {
        $this->templates[$dia]['activo'] = !$this->templates[$dia]['activo'];
    }

    public function saveAll()
    {
        $this->validate([
            'selectedLocationId' => 'required|exists:business_locations,id',
            'templates.*.hora_apertura' => 'required|date_format:H:i',
            'templates.*.hora_cierre' => 'required|date_format:H:i|after:templates.*.hora_apertura',
        ]);

        foreach ($this->templates as $template) {
            ScheduleTemplate::updateOrCreate(
                [
                    'location_id' => $this->selectedLocationId,
                    'dia_semana' => $template['dia_semana'],
                ],
                [
                    'hora_apertura' => $template['hora_apertura'],
                    'hora_cierre' => $template['hora_cierre'],
                    'activo' => $template['activo'],
                ]
            );
        }

        session()->flash('message', 'Horarios guardados correctamente');
    }

    public function render()
    {
        return view('livewire.schedule.manage-schedule');
    }
}
