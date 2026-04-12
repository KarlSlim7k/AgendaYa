<?php

namespace App\Livewire\Holidays;

use App\Livewire\Concerns\UsesBusinessLayout;
use App\Models\BusinessLocation;
use App\Models\ScheduleException;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class HolidaysList extends Component
{
    use WithPagination;
    use UsesBusinessLayout;

    public $holidays = [];
    public $locations = [];
    public $selectedLocation = '';
    public $showModal = false;
    public $editMode = false;
    public $selectedHoliday = null;
    
    // Form fields
    public $business_location_id;
    public $nombre;
    public $fecha;
    public $descripcion;
    public $tipo = 'cerrado'; // cerrado, horario_especial

    protected $rules = [
        'business_location_id' => 'required|exists:business_locations,id',
        'nombre' => 'required|string|max:100',
        'fecha' => 'required|date',
        'descripcion' => 'nullable|string|max:500',
        'tipo' => 'required|in:cerrado,horario_especial',
    ];

    public function mount()
    {
        $businessId = auth()->user()->current_business_id;
        $this->locations = BusinessLocation::where('business_id', $businessId)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();
        
        if ($this->locations->count() > 0 && !$this->selectedLocation) {
            $this->selectedLocation = $this->locations->first()->id;
        }
        
        $this->loadHolidays();
    }

    public function loadHolidays()
    {
        if (!$this->selectedLocation) {
            $this->holidays = collect([]);
            return;
        }

        $this->holidays = ScheduleException::where('business_location_id', $this->selectedLocation)
            ->whereDate('fecha', '>=', Carbon::now()->startOfYear())
            ->orderBy('fecha', 'asc')
            ->get();
    }

    public function updatedSelectedLocation()
    {
        $this->loadHolidays();
        $this->resetPage();
    }

    public function create()
    {
        $this->resetForm();
        $this->editMode = false;
        $this->business_location_id = $this->selectedLocation;
        $this->showModal = true;
    }

    public function edit($holidayId)
    {
        $this->selectedHoliday = ScheduleException::findOrFail($holidayId);
        
        $this->business_location_id = $this->selectedHoliday->business_location_id;
        $this->nombre = $this->selectedHoliday->nombre;
        $this->fecha = Carbon::parse($this->selectedHoliday->fecha)->format('Y-m-d');
        $this->descripcion = $this->selectedHoliday->descripcion;
        $this->tipo = $this->selectedHoliday->tipo;
        
        $this->editMode = true;
        $this->showModal = true;
    }

    public function save()
    {
        $validatedData = $this->validate();

        if ($this->editMode && $this->selectedHoliday) {
            $this->selectedHoliday->update($validatedData);
            session()->flash('message', 'Día festivo actualizado correctamente.');
        } else {
            // Check if date already exists
            $exists = ScheduleException::where('business_location_id', $validatedData['business_location_id'])
                ->whereDate('fecha', $validatedData['fecha'])
                ->exists();
            
            if ($exists) {
                $this->addError('fecha', 'Ya existe un día festivo o excepción para esta fecha.');
                return;
            }

            ScheduleException::create($validatedData);
            session()->flash('message', 'Día festivo creado correctamente.');
        }

        $this->showModal = false;
        $this->resetForm();
        $this->loadHolidays();
    }

    public function delete($holidayId)
    {
        $holiday = ScheduleException::findOrFail($holidayId);
        $holiday->delete();
        session()->flash('message', 'Día festivo eliminado correctamente.');
        $this->loadHolidays();
    }

    private function resetForm()
    {
        $this->business_location_id = $this->selectedLocation;
        $this->nombre = '';
        $this->fecha = '';
        $this->descripcion = '';
        $this->tipo = 'cerrado';
        $this->selectedHoliday = null;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function render()
    {
        return $this->renderInBusinessLayout('livewire.holidays.holidays-list', [
            'holidays' => $this->holidays,
        ], 'Días Festivos', 'Gestion');
    }
}
