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
    
    // Modal
    public $showDetailModal = false;
    public $selectedAppointment = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'estadoFilter' => ['except' => ''],
        'servicioFilter' => ['except' => ''],
        'empleadoFilter' => ['except' => ''],
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
        $this->resetPage();
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

        // Ordenar por fecha descendente
        $query->orderBy('fecha_hora_inicio', 'desc');

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
