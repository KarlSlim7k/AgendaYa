<?php

namespace App\Livewire\Business;

use App\Livewire\Concerns\UsesBusinessLayout;
use App\Models\Business;
use Livewire\Component;
use Livewire\WithFileUploads;

class BusinessProfile extends Component
{
    use WithFileUploads;
    use UsesBusinessLayout;

    public ?Business $business = null;
    public $nombre;
    public $razon_social;
    public $rfc;
    public $telefono;
    public $email;
    public $categoria;
    public $descripcion;

    protected function rules()
    {
        return [
            'nombre' => 'required|string|max:100',
            'razon_social' => 'nullable|string|max:200',
            'rfc' => 'nullable|string|max:13|regex:/^[A-Z&Ñ]{3,4}[0-9]{6}[A-Z0-9]{3}$/',
            'telefono' => 'required|string|max:20',
            'email' => 'required|email|max:100',
            'categoria' => 'required|string|in:peluqueria,clinica,taller,spa,consultorio,gimnasio,restaurante,otro',
            'descripcion' => 'nullable|string|max:1000',
        ];
    }

    public function mount()
    {
        $user = auth()->user();
        $businessId = $user->current_business_id;

        // If no current_business_id, try to get first business from roles
        if (!$businessId) {
            // Use DB query to avoid SoftDeletes issue on BusinessUserRole
            $firstRole = \Illuminate\Support\Facades\DB::table('business_user_roles')
                ->where('user_id', $user->id)
                ->select('business_id')
                ->first();
            
            if ($firstRole) {
                $businessId = $firstRole->business_id;
            }
        }

        if ($businessId) {
            // Load business directly by ID
            $this->business = Business::with('locations')->find($businessId);

            if ($this->business) {
                $this->nombre = $this->business->nombre;
                $this->razon_social = $this->business->razon_social;
                $this->rfc = $this->business->rfc;
                $this->telefono = $this->business->telefono;
                $this->email = $this->business->email;
                $this->categoria = $this->business->categoria;
                $this->descripcion = $this->business->descripcion;
            }
        }
    }

    public function save()
    {
        $validatedData = $this->validate();

        $this->business->update($validatedData);

        session()->flash('message', 'Perfil del negocio actualizado correctamente.');

        $this->dispatch('profile-updated');
    }

    public function render()
    {
        if (!$this->business) {
            return $this->renderInBusinessLayout('livewire.business.business-profile', [], 'Perfil del Negocio', 'Gestion');
        }

        return $this->renderInBusinessLayout('livewire.business.business-profile', [], 'Perfil del Negocio', 'Gestion');
    }
}
