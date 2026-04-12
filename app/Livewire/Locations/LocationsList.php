<?php

namespace App\Livewire\Locations;

use App\Livewire\Concerns\UsesBusinessLayout;
use App\Models\BusinessLocation;
use Livewire\Component;
use Livewire\WithFileUploads;

class LocationsList extends Component
{
    use WithFileUploads;
    use UsesBusinessLayout;

    public $locations = [];
    public $showModal = false;
    public $editMode = false;
    public $selectedLocation = null;
    
    // Form fields
    public $nombre;
    public $direccion;
    public $ciudad;
    public $estado;
    public $codigo_postal;
    public $telefono;
    public $email;
    public $descripcion;
    public $activo = true;

    protected $rules = [
        'nombre' => 'required|string|max:100',
        'direccion' => 'required|string|max:200',
        'ciudad' => 'required|string|max:100',
        'estado' => 'required|string|max:100',
        'codigo_postal' => 'required|string|max:10',
        'telefono' => 'nullable|string|max:20',
        'email' => 'nullable|email|max:100',
        'descripcion' => 'nullable|string|max:500',
    ];

    public function mount()
    {
        $businessId = auth()->user()->current_business_id;
        $this->locations = BusinessLocation::where('business_id', $businessId)
            ->orderBy('nombre')
            ->get();
    }

    public function create()
    {
        $this->resetForm();
        $this->editMode = false;
        $this->showModal = true;
    }

    public function edit($locationId)
    {
        $this->selectedLocation = BusinessLocation::findOrFail($locationId);
        
        $this->nombre = $this->selectedLocation->nombre;
        $this->direccion = $this->selectedLocation->direccion;
        $this->ciudad = $this->selectedLocation->ciudad;
        $this->estado = $this->selectedLocation->estado;
        $this->codigo_postal = $this->selectedLocation->codigo_postal;
        $this->telefono = $this->selectedLocation->telefono;
        $this->email = $this->selectedLocation->email;
        $this->descripcion = $this->selectedLocation->descripcion;
        $this->activo = $this->selectedLocation->activo;
        
        $this->editMode = true;
        $this->showModal = true;
    }

    public function save()
    {
        $validatedData = $this->validate();

        $businessId = auth()->user()->current_business_id;

        if ($this->editMode && $this->selectedLocation) {
            $this->selectedLocation->update($validatedData);
            session()->flash('message', 'Sucursal actualizada correctamente.');
        } else {
            BusinessLocation::create(array_merge($validatedData, [
                'business_id' => $businessId,
            ]));
            session()->flash('message', 'Sucursal creada correctamente.');
        }

        $this->showModal = false;
        $this->resetForm();
        $this->mount();
    }

    public function toggleActivo($locationId)
    {
        $location = BusinessLocation::findOrFail($locationId);
        $location->update(['activo' => !$location->activo]);
        $this->mount();
    }

    public function delete($locationId)
    {
        $location = BusinessLocation::findOrFail($locationId);
        
        // Check if location has appointments
        if ($location->appointments()->count() > 0) {
            session()->flash('error', 'No se puede eliminar una sucursal con citas registradas.');
            return;
        }

        $location->delete();
        session()->flash('message', 'Sucursal eliminada correctamente.');
        $this->mount();
    }

    private function resetForm()
    {
        $this->nombre = '';
        $this->direccion = '';
        $this->ciudad = '';
        $this->estado = '';
        $this->codigo_postal = '';
        $this->telefono = '';
        $this->email = '';
        $this->descripcion = '';
        $this->activo = true;
        $this->selectedLocation = null;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function render()
    {
        return $this->renderInBusinessLayout('livewire.locations.locations-list', [
            'locations' => $this->locations,
        ], 'Sucursales', 'Gestion');
    }
}
