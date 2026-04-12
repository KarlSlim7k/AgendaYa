<?php

namespace App\Livewire\Clients;

use App\Livewire\Concerns\UsesBusinessLayout;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ClientsList extends Component
{
    use WithPagination;
    use UsesBusinessLayout;

    public $search = '';
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $showDetailModal = false;
    public $selectedClient = null;
    public $clientAppointments = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'sortBy' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function sortByField($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function viewDetail($clientId)
    {
        $this->selectedClient = User::find($clientId);
        
        if (!$this->selectedClient) {
            session()->flash('error', 'Cliente no encontrado.');
            return;
        }

        // Get client's appointments with current business
        $this->clientAppointments = Appointment::where('user_id', $clientId)
            ->where('business_id', auth()->user()->current_business_id)
            ->with(['service', 'employee'])
            ->orderBy('fecha_hora_inicio', 'desc')
            ->limit(10)
            ->get()
            ->toArray();

        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedClient = null;
        $this->clientAppointments = [];
    }

    public function render()
    {
        $businessId = auth()->user()->current_business_id;

        // Check if 'apellidos' column exists (compatibility with production)
        $hasApellidos = Schema::hasColumn('users', 'apellidos');

        // Get unique clients who have appointments with this business
        $query = User::select('users.id', 'users.nombre', 'users.email', 
                              'users.telefono', 'users.created_at')
            ->when($hasApellidos, fn($q) => $q->addSelect('users.apellidos'))
            ->join('appointments', 'users.id', '=', 'appointments.user_id')
            ->where('appointments.business_id', $businessId)
            ->groupBy('users.id', 'users.nombre', 'users.email', 
                      'users.telefono', 'users.created_at')
            ->when($hasApellidos, fn($q) => $q->addGroupBy('users.apellidos'));

        if ($this->search) {
            $query->where(function($q) use ($hasApellidos) {
                $q->where('users.nombre', 'like', '%' . $this->search . '%')
                  ->orWhere('users.email', 'like', '%' . $this->search . '%')
                  ->orWhere('users.telefono', 'like', '%' . $this->search . '%');
                if ($hasApellidos) {
                    $q->orWhere('users.apellidos', 'like', '%' . $this->search . '%');
                }
            });
        }

        // Add appointment count
        $query->addSelect(DB::raw('COUNT(appointments.id) as total_appointments'))
              ->addSelect(DB::raw('MAX(appointments.fecha_hora_inicio) as last_appointment'));

        // Sorting
        if ($this->sortBy === 'total_appointments') {
            $query->orderByRaw('COUNT(appointments.id) ' . $this->sortDirection);
        } elseif ($this->sortBy === 'last_appointment') {
            $query->orderByRaw('MAX(appointments.fecha_hora_inicio) ' . $this->sortDirection);
        } else {
            $query->orderBy('users.' . $this->sortBy, $this->sortDirection);
        }

        $clients = $query->paginate(20);

        return $this->renderInBusinessLayout('livewire.clients.clients-list', [
            'clients' => $clients,
            'hasApellidos' => $hasApellidos,
        ], 'Clientes', 'Principal');
    }
}
