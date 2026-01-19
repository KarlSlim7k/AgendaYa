<?php

namespace App\Livewire\Services;

use App\Models\Service;
use Livewire\Component;
use Livewire\WithPagination;

class ServicesList extends Component
{
    use WithPagination;

    public $search = '';
    public $filterActivo = '';
    public $showModal = false;
    public $selectedService = null;

    protected $queryString = ['search', 'filterActivo'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterActivo()
    {
        $this->resetPage();
    }

    public function toggleActivo($serviceId)
    {
        $service = Service::findOrFail($serviceId);
        
        if ($service->business_id !== auth()->user()->current_business_id) {
            session()->flash('error', 'No autorizado');
            return;
        }

        $service->update(['activo' => !$service->activo]);
        session()->flash('message', 'Servicio actualizado correctamente');
    }

    public function viewDetails($serviceId)
    {
        $this->selectedService = Service::with('employees')->findOrFail($serviceId);
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->selectedService = null;
    }

    public $confirmingDeletion = false;
    public $serviceToDelete = null;

    public function confirmDelete($serviceId)
    {
        $this->serviceToDelete = $serviceId;
        $this->confirmingDeletion = true;
    }

    public function cancelDelete()
    {
        $this->confirmingDeletion = false;
        $this->serviceToDelete = null;
    }

    public function delete()
    {
        if (!$this->serviceToDelete) return;

        $service = Service::findOrFail($this->serviceToDelete);
        
        if ($service->business_id !== auth()->user()->current_business_id) {
            session()->flash('error', 'No autorizado');
            $this->cancelDelete();
            return;
        }

        // Verificar si tiene citas activas
        if ($service->appointments()->whereIn('estado', ['pending', 'confirmed'])->exists()) {
            session()->flash('error', 'No se puede eliminar un servicio con citas activas');
            $this->cancelDelete();
            return;
        }

        $service->delete();
        session()->flash('message', 'Servicio eliminado correctamente');
        $this->cancelDelete();
    }

    public function render()
    {
        $query = Service::query();

        if ($this->search) {
            $query->where(function($q) {
                $q->where('nombre', 'like', '%' . $this->search . '%')
                  ->orWhere('descripcion', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterActivo !== '') {
            $query->where('activo', $this->filterActivo);
        }

        $services = $query->orderBy('nombre')->paginate(15);

        return view('livewire.services.services-list', [
            'services' => $services
        ]);
    }
}
