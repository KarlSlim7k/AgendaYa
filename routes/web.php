<?php

use App\Http\Controllers\ProfileController;
use App\Livewire\Appointments\AppointmentsList;
use App\Livewire\Appointments\CreateAppointmentForm;
use App\Livewire\Business\BusinessProfile;
use App\Livewire\Dashboard\BusinessDashboard;
use App\Livewire\Reports\AppointmentsReport;
use App\Livewire\Services\ServicesList;
use App\Livewire\Services\CreateEditService;
use App\Livewire\Employees\EmployeesList;
use App\Livewire\Employees\CreateEditEmployee;
use App\Livewire\Schedule\ManageSchedule;
use App\Livewire\Schedule\ManageExceptions;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard principal
    Route::get('/dashboard', BusinessDashboard::class)->name('dashboard');
    
    // Perfil de usuario
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Perfil del negocio
    Route::get('/business/profile', BusinessProfile::class)->name('business.profile');
    
    // Rutas de Citas
    Route::get('/appointments', AppointmentsList::class)->name('appointments.index');
    Route::get('/appointments/create', CreateAppointmentForm::class)->name('appointments.create');
    
    // Rutas de Servicios
    Route::get('/services', ServicesList::class)->name('services.index');
    Route::get('/services/create', CreateEditService::class)->name('services.create');
    Route::get('/services/{serviceId}/edit', CreateEditService::class)->name('services.edit');
    
    // Rutas de Empleados
    Route::get('/employees', EmployeesList::class)->name('employees.index');
    Route::get('/employees/create', CreateEditEmployee::class)->name('employees.create');
    Route::get('/employees/{employeeId}/edit', CreateEditEmployee::class)->name('employees.edit');
    
    // Rutas de Horarios
    Route::get('/schedules', ManageSchedule::class)->name('schedules.index');
    Route::get('/schedules/exceptions', ManageExceptions::class)->name('schedules.exceptions');
    
    // Ruta de Reportes
    Route::get('/reports', AppointmentsReport::class)->name('reports.index');
});

require __DIR__.'/auth.php';
