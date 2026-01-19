<?php

namespace App\Livewire\Schedule;

use App\Models\BusinessLocation;
use App\Models\ScheduleException;
use Livewire\Component;
use Livewire\WithPagination;

class ManageExceptions extends Component
{
    use WithPagination;

    public $locations = [];
    public $selectedLocationId = null;
    public $showForm = false;
    
    // Form fields
    public $exceptionId = null;
    public $tipo = 'feriado';
    public $motivo = '';
    public $fecha_inicio = '';
    public $fecha_fin = '';

    public function mount()
    {
        $this->locations = BusinessLocation::orderBy('nombre')->get();
        
        if ($this->locations->count() > 0) {
            $this->selectedLocationId = $this->locations->first()->id;
        }
    }

    public function updatedSelectedLocationId()
    {
        $this->resetPage();
    }

    public function showCreateForm()
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit($exceptionId)
    {
        $exception = ScheduleException::findOrFail($exceptionId);
        
        if ($exception->location_id !== (int)$this->selectedLocationId) {
            session()->flash('error', 'No autorizado');
            return;
        }

        $this->exceptionId = $exception->id;
        $this->tipo = $exception->tipo;
        $this->motivo = $exception->motivo;
        $this->fecha_inicio = $exception->fecha_inicio->format('Y-m-d');
        $this->fecha_fin = $exception->fecha_fin->format('Y-m-d');
        $this->showForm = true;
    }

    public function save()
    {
        $this->validate([
            'tipo' => 'required|in:feriado,vacaciones,cierre',
            'motivo' => 'required|string|max:255',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ]);

        $data = [
            'location_id' => $this->selectedLocationId,
            'tipo' => $this->tipo,
            'motivo' => $this->motivo,
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin' => $this->fecha_fin,
        ];

        if ($this->exceptionId) {
            ScheduleException::findOrFail($this->exceptionId)->update($data);
            session()->flash('message', 'Excepción actualizada correctamente');
        } else {
            ScheduleException::create($data);
            session()->flash('message', 'Excepción creada correctamente');
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public $confirmingDeletion = false;
    public $exceptionToDelete = null;

    public function confirmDelete($exceptionId)
    {
        $this->exceptionToDelete = $exceptionId;
        $this->confirmingDeletion = true;
    }

    public function cancelDelete()
    {
        $this->confirmingDeletion = false;
        $this->exceptionToDelete = null;
    }

    public function delete()
    {
        if (!$this->exceptionToDelete) return;

        $exception = ScheduleException::findOrFail($this->exceptionToDelete);
        
        if ($exception->location_id !== (int)$this->selectedLocationId) {
            session()->flash('error', 'No autorizado');
            $this->cancelDelete();
            return;
        }

        $exception->delete();
        session()->flash('message', 'Excepción eliminada correctamente');
        $this->cancelDelete();
    }

    public function cancelForm()
    {
        $this->resetForm();
        $this->showForm = false;
    }

    private function resetForm()
    {
        $this->exceptionId = null;
        $this->tipo = 'feriado';
        $this->motivo = '';
        $this->fecha_inicio = '';
        $this->fecha_fin = '';
    }

    public function render()
    {
        $exceptions = collect();
        
        if ($this->selectedLocationId) {
            $exceptions = ScheduleException::where('location_id', $this->selectedLocationId)
                ->orderBy('fecha_inicio', 'desc')
                ->paginate(15);
        }

        return view('livewire.schedule.manage-exceptions', [
            'exceptions' => $exceptions
        ]);
    }
}
