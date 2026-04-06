<?php

namespace App\Livewire\Schedule;

use App\Livewire\Concerns\UsesBusinessLayout;
use App\Models\BusinessLocation;
use App\Models\ScheduleException;
use Livewire\Component;
use Livewire\WithPagination;

class ManageExceptions extends Component
{
    use WithPagination;
    use UsesBusinessLayout;

    public $locations = [];
    public $selectedLocationId = null;
    public $showForm = false;
    
    // Form fields
    public $exceptionId = null;
    public $tipo = 'feriado';
    public $motivo = '';
    public $fecha = '';
    public $todo_el_dia = true;
    public $hora_inicio = '';
    public $hora_fin = '';

    public function mount()
    {
        $this->locations = BusinessLocation::active()->orderBy('nombre')->get();
        
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
        $this->fecha = now()->format('Y-m-d');
        $this->showForm = true;
    }

    public function edit($exceptionId)
    {
        $exception = ScheduleException::findOrFail($exceptionId);
        
        if ($exception->business_location_id !== (int) $this->selectedLocationId) {
            session()->flash('error', 'No autorizado');
            return;
        }

        $this->exceptionId = $exception->id;
        $this->tipo = $exception->tipo;
        $this->motivo = $exception->motivo;
        $this->fecha = $exception->fecha->format('Y-m-d');
        $this->todo_el_dia = (bool) $exception->todo_el_dia;
        $this->hora_inicio = $exception->hora_inicio ?? '';
        $this->hora_fin = $exception->hora_fin ?? '';
        $this->showForm = true;
    }

    public function save()
    {
        $this->validate([
            'tipo' => 'required|in:feriado,vacaciones,cierre',
            'motivo' => 'nullable|string|max:255',
            'fecha' => 'required|date',
            'todo_el_dia' => 'boolean',
            'hora_inicio' => 'nullable|date_format:H:i|required_if:todo_el_dia,false',
            'hora_fin' => 'nullable|date_format:H:i|after:hora_inicio|required_if:todo_el_dia,false',
        ]);

        $data = [
            'business_location_id' => $this->selectedLocationId,
            'tipo' => $this->tipo,
            'motivo' => $this->motivo,
            'fecha' => $this->fecha,
            'todo_el_dia' => $this->todo_el_dia,
            'hora_inicio' => $this->todo_el_dia ? null : $this->hora_inicio,
            'hora_fin' => $this->todo_el_dia ? null : $this->hora_fin,
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
        
        if ($exception->business_location_id !== (int) $this->selectedLocationId) {
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
        $this->fecha = '';
        $this->todo_el_dia = true;
        $this->hora_inicio = '';
        $this->hora_fin = '';
    }

    public function render()
    {
        $exceptions = collect();
        
        if ($this->selectedLocationId) {
            $exceptions = ScheduleException::where('business_location_id', $this->selectedLocationId)
                ->orderBy('fecha', 'desc')
                ->paginate(15);
        }

        return $this->renderInBusinessLayout('livewire.schedule.manage-exceptions', [
            'exceptions' => $exceptions
        ], 'Excepciones de Horario', 'Gestion');
    }
}
