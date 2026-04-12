<?php

namespace App\Livewire\Reports;

use App\Livewire\Concerns\UsesBusinessLayout;
use App\Models\Employee;
use App\Models\Service;
use App\Services\ReportsService;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class AppointmentsReport extends Component
{
    use WithPagination;
    use UsesBusinessLayout;

    public $fechaInicio;
    public $fechaFin;
    public $serviceId = '';
    public $employeeId = '';
    public $estado = '';

    protected ReportsService $reportsService;

    public function boot(ReportsService $reportsService)
    {
        $this->reportsService = $reportsService;
    }

    public function mount()
    {
        // Default: último mes
        $this->fechaInicio = Carbon::now()->subMonth()->format('Y-m-d');
        $this->fechaFin = Carbon::now()->format('Y-m-d');
    }

    public function updatedFechaInicio()
    {
        $this->resetPage();
    }

    public function updatedFechaFin()
    {
        $this->resetPage();
    }

    public function updatedServiceId()
    {
        $this->resetPage();
    }

    public function updatedEmployeeId()
    {
        $this->resetPage();
    }

    public function updatedEstado()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->fechaInicio = Carbon::now()->subMonth()->format('Y-m-d');
        $this->fechaFin = Carbon::now()->format('Y-m-d');
        $this->serviceId = '';
        $this->employeeId = '';
        $this->estado = '';
        $this->resetPage();
    }

    private function resolveBusiness()
    {
        $user = auth()->user();
        
        // If current_business_id is set but relation is not loaded, load it
        if (!$user->currentBusiness && $user->current_business_id) {
            $user->load('currentBusiness');
        }
        
        $business = $user->currentBusiness;

        // If still no business, try to get it directly with the ID (bypassing relation)
        if (!$business && $user->current_business_id) {
            $business = \App\Models\Business::find($user->current_business_id);
        }

        return $business;
    }

    public function exportToCsv()
    {
        $business = $this->resolveBusiness();
        if (!$business) return;
        
        $appointments = $this->reportsService->getAppointmentsReport(
            $business,
            Carbon::parse($this->fechaInicio),
            Carbon::parse($this->fechaFin),
            $this->serviceId ?: null,
            $this->employeeId ?: null,
            $this->estado ?: null
        )->get();

        $filename = 'reporte_citas_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() use ($appointments) {
            $file = fopen('php://output', 'w');
            
            // BOM para UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Encabezados
            fputcsv($file, [
                'ID',
                'Fecha',
                'Hora Inicio',
                'Hora Fin',
                'Cliente',
                'Email',
                'Servicio',
                'Empleado',
                'Estado',
                'Precio',
                'Notas'
            ]);

            // Datos
            foreach ($appointments as $appointment) {
                fputcsv($file, [
                    $appointment->id,
                    $appointment->fecha_hora_inicio->format('Y-m-d'),
                    $appointment->fecha_hora_inicio->format('H:i'),
                    $appointment->fecha_hora_fin->format('H:i'),
                    $appointment->user->nombre,
                    $appointment->user->email,
                    $appointment->service->nombre,
                    $appointment->employee->nombre,
                    $appointment->estado,
                    number_format($appointment->service->precio, 2),
                    $appointment->notas_cliente ?? ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function render()
    {
        $business = $this->resolveBusiness();
        if (!$business) {
            $empty = new LengthAwarePaginator([], 0, 20);
            return $this->renderInBusinessLayout('livewire.reports.appointments-report', [
                'appointments' => $empty,
                'services' => collect(),
                'employees' => collect(),
            ], 'Reportes', 'Gestion');
        }

        $appointments = $this->reportsService->getAppointmentsReport(
            $business,
            Carbon::parse($this->fechaInicio),
            Carbon::parse($this->fechaFin),
            $this->serviceId ?: null,
            $this->employeeId ?: null,
            $this->estado ?: null
        )->paginate(20);

        $services = Service::where('business_id', $business->id)->orderBy('nombre')->get();
        $employees = Employee::where('business_id', $business->id)->orderBy('nombre')->get();

        return $this->renderInBusinessLayout('livewire.reports.appointments-report', [
            'appointments' => $appointments,
            'services' => $services,
            'employees' => $employees
        ], 'Reportes', 'Gestion');
    }
}
