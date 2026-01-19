<?php

namespace App\Livewire\Reports;

use Livewire\Component;
use Livewire\WithPagination;
use App\Services\ReportsService;
use App\Models\Service;
use App\Models\Employee;
use Carbon\Carbon;

class AppointmentsReport extends Component
{
    use WithPagination;

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

    public function exportToCsv()
    {
        $business = auth()->user()->currentBusiness();
        
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
        $business = auth()->user()->currentBusiness();
        
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

        return view('livewire.reports.appointments-report', [
            'appointments' => $appointments,
            'services' => $services,
            'employees' => $employees
        ]);
    }
}
