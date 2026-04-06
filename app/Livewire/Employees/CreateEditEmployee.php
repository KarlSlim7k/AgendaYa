<?php

namespace App\Livewire\Employees;

use App\Livewire\Concerns\UsesBusinessLayout;
use App\Models\Employee;
use App\Models\Service;
use Livewire\Component;

class CreateEditEmployee extends Component
{
    use UsesBusinessLayout;

    public $employeeId = null;
    public $nombre = '';
    public $email = '';
    public $telefono = '';
    public $selectedServices = [];
    public $availableServices = [];

    public function mount($employeeId = null)
    {
        $this->availableServices = Service::where('activo', true)
            ->orderBy('nombre')
            ->get();

        if ($employeeId) {
            $employee = Employee::with('services')->findOrFail($employeeId);
            
            if ($employee->business_id !== auth()->user()->current_business_id) {
                abort(403, 'No autorizado');
            }

            $this->employeeId = $employee->id;
            $this->nombre = $employee->nombre;
            $this->email = $employee->email;
            $this->telefono = $employee->telefono;
            $this->selectedServices = $employee->services->pluck('id')->toArray();
        }
    }

    protected function rules()
    {
        $rules = [
            'nombre' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'selectedServices' => 'required|array|min:1',
            'selectedServices.*' => 'exists:services,id',
        ];

        if ($this->employeeId) {
            $rules['email'] = 'required|email|unique:employees,email,' . $this->employeeId . ',id,business_id,' . auth()->user()->current_business_id;
        } else {
            $rules['email'] = 'required|email|unique:employees,email,NULL,id,business_id,' . auth()->user()->current_business_id;
        }

        return $rules;
    }

    public function save()
    {
        $this->validate();

        $data = [
            'business_id' => auth()->user()->current_business_id,
            'nombre' => $this->nombre,
            'email' => $this->email,
            'telefono' => $this->telefono,
        ];

        if ($this->employeeId) {
            $employee = Employee::findOrFail($this->employeeId);
            $employee->update($data);
            $employee->services()->sync($this->selectedServices);
            session()->flash('message', 'Empleado actualizado correctamente');
        } else {
            $employee = Employee::create($data);
            $employee->services()->sync($this->selectedServices);
            session()->flash('message', 'Empleado creado correctamente');
        }

        return redirect()->route('business.employees.index');
    }

    public function cancel()
    {
        return redirect()->route('business.employees.index');
    }

    public function render()
    {
        return $this->renderInBusinessLayout(
            'livewire.employees.create-edit-employee',
            [],
            $this->employeeId ? 'Editar Empleado' : 'Nuevo Empleado',
            'Principal'
        );
    }
}
