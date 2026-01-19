<?php

namespace App\Livewire\Services;

use App\Models\Service;
use Livewire\Component;

class CreateEditService extends Component
{
    public $serviceId = null;
    public $nombre = '';
    public $descripcion = '';
    public $precio = '';
    public $duracion_minutos = 30;
    public $buffer_pre_minutos = 0;
    public $buffer_post_minutos = 0;
    public $requiere_confirmacion = false;
    public $activo = true;

    public function mount($serviceId = null)
    {
        if ($serviceId) {
            $service = Service::findOrFail($serviceId);
            
            if ($service->business_id !== auth()->user()->current_business_id) {
                abort(403, 'No autorizado');
            }

            $this->serviceId = $service->id;
            $this->nombre = $service->nombre;
            $this->descripcion = $service->descripcion;
            $this->precio = $service->precio;
            $this->duracion_minutos = $service->duracion_minutos;
            $this->buffer_pre_minutos = $service->buffer_pre_minutos;
            $this->buffer_post_minutos = $service->buffer_post_minutos;
            $this->requiere_confirmacion = $service->requiere_confirmacion;
            $this->activo = $service->activo;
        }
    }

    protected function rules()
    {
        return [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'precio' => 'required|numeric|min:0|max:999999.99',
            'duracion_minutos' => 'required|integer|min:15|max:480',
            'buffer_pre_minutos' => 'nullable|integer|min:0|max:120',
            'buffer_post_minutos' => 'nullable|integer|min:0|max:120',
            'requiere_confirmacion' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    public function save()
    {
        $this->validate();

        $data = [
            'business_id' => auth()->user()->current_business_id,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'precio' => $this->precio,
            'duracion_minutos' => $this->duracion_minutos,
            'buffer_pre_minutos' => $this->buffer_pre_minutos ?? 0,
            'buffer_post_minutos' => $this->buffer_post_minutos ?? 0,
            'requiere_confirmacion' => $this->requiere_confirmacion,
            'activo' => $this->activo,
        ];

        if ($this->serviceId) {
            $service = Service::findOrFail($this->serviceId);
            $service->update($data);
            session()->flash('message', 'Servicio actualizado correctamente');
        } else {
            Service::create($data);
            session()->flash('message', 'Servicio creado correctamente');
        }

        return redirect()->route('services.index');
    }

    public function cancel()
    {
        return redirect()->route('services.index');
    }

    public function render()
    {
        return view('livewire.services.create-edit-service');
    }
}
