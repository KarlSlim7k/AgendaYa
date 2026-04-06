<?php

namespace App\Livewire\Employees;

use App\Livewire\Concerns\UsesBusinessLayout;
use App\Models\Employee;
use Livewire\Component;
use Livewire\WithPagination;

class EmployeesList extends Component
{
    use WithPagination;
    use UsesBusinessLayout;

    public $search = '';
    public $showModal = false;
    public $selectedEmployee = null;

    protected $queryString = ['search'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function viewDetails($employeeId)
    {
        $this->selectedEmployee = Employee::with('services')->findOrFail($employeeId);
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->selectedEmployee = null;
    }

    public function delete($employeeId)
    {
        if (!$this->employeeToDelete) {
            $this->employeeToDelete = $employeeId;
        }

        $employee = Employee::findOrFail($this->employeeToDelete);
        
        if ($employee->business_id !== auth()->user()->current_business_id) {
            session()->flash('error', 'No autorizado');
            $this->cancelDelete();
            return;
        }

        // Verificar si tiene citas futuras
        if ($employee->appointments()->whereIn('estado', ['pending', 'confirmed'])->exists()) {
            session()->flash('error', 'No se puede eliminar un empleado con citas activas');
            $this->cancelDelete();
            return;
        }

        $employee->delete();
        session()->flash('message', 'Empleado eliminado correctamente');
        $this->cancelDelete();
    }

    public $confirmingDeletion = false;
    public $employeeToDelete = null;

    public function confirmDelete($employeeId)
    {
        $this->employeeToDelete = $employeeId;
        $this->confirmingDeletion = true;
    }

    public function cancelDelete()
    {
        $this->confirmingDeletion = false;
        $this->employeeToDelete = null;
    }

    public function render()
    {
        $query = Employee::with('services');

        if ($this->search) {
            $query->where(function($q) {
                $q->where('nombre', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('telefono', 'like', '%' . $this->search . '%');
            });
        }

        $employees = $query->orderBy('nombre')->paginate(15);

        return $this->renderInBusinessLayout('livewire.employees.employees-list', [
            'employees' => $employees
        ], 'Empleados', 'Principal');
    }
}
