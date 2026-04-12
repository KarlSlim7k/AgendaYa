<?php

namespace App\Livewire\Appointments;

use App\Livewire\Concerns\UsesBusinessLayout;
use App\Models\Appointment;
use App\Models\Employee;
use App\Models\Service;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class AppointmentsList extends Component
{
    use WithPagination;
    use UsesBusinessLayout;

    // Filtros
    public $search = '';
    public $estadoFilter = '';
    public $servicioFilter = '';
    public $empleadoFilter = '';
    public $fechaDesde = '';
    public $fechaHasta = '';
    
    // Configuración
    public $perPage = 15;
    public $sortBy = 'fecha_hora_inicio';
    public $sortDirection = 'desc';

    // Modal
    public $showDetailModal = false;
    public $selectedAppointment = null;
    
    // Bulk actions
    public $selectedAppointments = [];
    public $showBulkActions = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'estadoFilter' => ['except' => ''],
        'servicioFilter' => ['except' => ''],
        'empleadoFilter' => ['except' => ''],
        'sortBy' => ['except' => 'fecha_hora_inicio'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function mount()
    {
        // Inicializar filtros de fecha (últimos 30 días por defecto)
        $this->fechaDesde = Carbon::now()->subDays(30)->format('Y-m-d');
        $this->fechaHasta = Carbon::now()->addDays(7)->format('Y-m-d');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingEstadoFilter()
    {
        $this->resetPage();
    }

    public function updatingServicioFilter()
    {
        $this->resetPage();
    }

    public function updatingEmpleadoFilter()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->reset(['search', 'estadoFilter', 'servicioFilter', 'empleadoFilter']);
        $this->fechaDesde = Carbon::now()->subDays(30)->format('Y-m-d');
        $this->fechaHasta = Carbon::now()->addDays(7)->format('Y-m-d');
        $this->sortBy = 'fecha_hora_inicio';
        $this->sortDirection = 'desc';
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

    public function toggleSelectAppointment($appointmentId)
    {
        if (in_array($appointmentId, $this->selectedAppointments)) {
            $this->selectedAppointments = array_values(array_diff($this->selectedAppointments, [$appointmentId]));
        } else {
            $this->selectedAppointments[] = $appointmentId;
        }
    }

    public function selectAllOnPage()
    {
        $appointmentsOnPage = $this->getAppointmentsOnCurrentPage();
        $this->selectedAppointments = $appointmentsOnPage->pluck('id')->toArray();
    }

    public function clearSelection()
    {
        $this->selectedAppointments = [];
    }

    public function bulkCancelAppointments()
    {
        if (empty($this->selectedAppointments)) {
            session()->flash('error', 'No hay citas seleccionadas.');
            return;
        }

        $cancelled = 0;
        $appointments = Appointment::whereIn('id', $this->selectedAppointments)
            ->whereIn('estado', [Appointment::ESTADO_PENDING, Appointment::ESTADO_CONFIRMED])
            ->get();

        foreach ($appointments as $appointment) {
            try {
                $appointment->cambiarEstado(Appointment::ESTADO_CANCELLED, [
                    'cancelada_por_user_id' => auth()->id(),
                    'motivo_cancelacion' => 'Cancelación masiva desde el panel',
                ]);
                $cancelled++;
            } catch (\Exception $e) {
                // Skip failed cancellations
            }
        }

        session()->flash('message', "Se cancelaron {$cancelled} citas exitosamente.");
        $this->clearSelection();
    }

    public function bulkConfirmAppointments()
    {
        if (empty($this->selectedAppointments)) {
            session()->flash('error', 'No hay citas seleccionadas.');
            return;
        }

        $confirmed = 0;
        $appointments = Appointment::whereIn('id', $this->selectedAppointments)
            ->where('estado', Appointment::ESTADO_PENDING)
            ->get();

        foreach ($appointments as $appointment) {
            try {
                $appointment->cambiarEstado(Appointment::ESTADO_CONFIRMED);
                $confirmed++;
            } catch (\Exception $e) {
                // Skip failed confirmations
            }
        }

        session()->flash('message', "Se confirmaron {$confirmed} citas exitosamente.");
        $this->clearSelection();
    }

    private function getAppointmentsOnCurrentPage()
    {
        $user = auth()->user();
        $query = Appointment::with(['user', 'service', 'employee'])
            ->where('business_id', $user->current_business_id);

        // Apply same filters as render method
        if ($this->search) {
            $query->whereHas('user', function ($q) {
                $q->where('nombre', 'like', '%' . $this->search . '%')
                    ->orWhere('apellidos', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->estadoFilter) {
            $query->where('estado', $this->estadoFilter);
        }

        if ($this->servicioFilter) {
            $query->where('service_id', $this->servicioFilter);
        }

        if ($this->empleadoFilter) {
            $query->where('employee_id', $this->empleadoFilter);
        }

        if ($this->fechaDesde) {
            $query->whereDate('fecha_hora_inicio', '>=', $this->fechaDesde);
        }

        if ($this->fechaHasta) {
            $query->whereDate('fecha_hora_inicio', '<=', $this->fechaHasta);
        }

        $query->orderBy($this->sortBy, $this->sortDirection);

        return $query->forPage($this->getPage(), $this->perPage);
    }

    public function viewDetail($appointmentId)
    {
        $this->selectedAppointment = Appointment::with(['user', 'service', 'employee', 'business'])
            ->findOrFail($appointmentId);
        
        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedAppointment = null;
    }

    public function cancelAppointment($appointmentId)
    {
        $appointment = Appointment::findOrFail($appointmentId);
        
        // Validar que se puede cancelar
        if (!in_array($appointment->estado, [Appointment::ESTADO_PENDING, Appointment::ESTADO_CONFIRMED])) {
            session()->flash('error', 'Esta cita no se puede cancelar.');
            return;
        }

        try {
            $appointment->cambiarEstado(Appointment::ESTADO_CANCELLED, [
                'cancelada_por_user_id' => auth()->id(),
                'motivo_cancelacion' => 'Cancelada desde el panel administrativo',
            ]);

            session()->flash('success', 'Cita cancelada exitosamente.');
            $this->closeDetailModal();
        } catch (\Exception $e) {
            session()->flash('error', 'Error al cancelar la cita: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $user = auth()->user();
        
        // Construir query base
        $query = Appointment::with(['user', 'service', 'employee'])
            ->where('business_id', $user->current_business_id);

        // Aplicar filtros
        if ($this->search) {
            $query->whereHas('user', function ($q) {
                $q->where('nombre', 'like', '%' . $this->search . '%')
                    ->orWhere('apellidos', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->estadoFilter) {
            $query->where('estado', $this->estadoFilter);
        }

        if ($this->servicioFilter) {
            $query->where('service_id', $this->servicioFilter);
        }

        if ($this->empleadoFilter) {
            $query->where('employee_id', $this->empleadoFilter);
        }

        if ($this->fechaDesde) {
            $query->whereDate('fecha_hora_inicio', '>=', $this->fechaDesde);
        }

        if ($this->fechaHasta) {
            $query->whereDate('fecha_hora_inicio', '<=', $this->fechaHasta);
        }

        // Ordenar según configuración
        $allowedSortFields = ['fecha_hora_inicio', 'estado', 'user', 'service', 'employee'];
        if (!in_array($this->sortBy, $allowedSortFields)) {
            $this->sortBy = 'fecha_hora_inicio';
        }

        // Handle relationship fields
        if ($this->sortBy === 'user') {
            $query->leftJoin('users', 'appointments.user_id', '=', 'users.id')
                  ->orderBy('users.nombre', $this->sortDirection)
                  ->select('appointments.*');
        } elseif ($this->sortBy === 'service') {
            $query->leftJoin('services', 'appointments.service_id', '=', 'services.id')
                  ->orderBy('services.nombre', $this->sortDirection)
                  ->select('appointments.*');
        } elseif ($this->sortBy === 'employee') {
            $query->leftJoin('employees', 'appointments.employee_id', '=', 'employees.id')
                  ->orderBy('employees.nombre', $this->sortDirection)
                  ->select('appointments.*');
        } else {
            $query->orderBy($this->sortBy, $this->sortDirection);
        }

        $appointments = $query->paginate($this->perPage);

        // Cargar opciones de filtros
        $servicios = Service::where('business_id', $user->current_business_id)
            ->where('activo', true)
            ->get();

        $empleados = Employee::where('business_id', $user->current_business_id)
            ->whereNotIn('estado', ['baja'])
            ->get();

        return $this->renderInBusinessLayout('livewire.appointments.appointments-list', [
            'appointments' => $appointments,
            'servicios' => $servicios,
            'empleados' => $empleados,
            'estados' => [
                Appointment::ESTADO_PENDING => 'Pendiente',
                Appointment::ESTADO_CONFIRMED => 'Confirmada',
                Appointment::ESTADO_COMPLETED => 'Completada',
                Appointment::ESTADO_CANCELLED => 'Cancelada',
                Appointment::ESTADO_NO_SHOW => 'No asistió',
            ],
        ], 'Citas', 'Principal');
    }
}
