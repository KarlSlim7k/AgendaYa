<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Livewire\Appointments\AppointmentsList;
use App\Livewire\Appointments\CreateAppointmentForm;
use App\Livewire\Business\BusinessProfile;
use App\Livewire\Reports\AppointmentsReport;
use App\Livewire\Services\ServicesList;
use App\Livewire\Services\CreateEditService;
use App\Livewire\Employees\EmployeesList;
use App\Livewire\Employees\CreateEditEmployee;
use App\Livewire\Schedule\ManageSchedule;
use App\Livewire\Schedule\ManageExceptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard principal
    Route::get('/dashboard', function (Request $request) {
        if (Gate::forUser($request->user())->allows('platform-admin')) {
            return redirect()->route('admin.dashboard');
        }

        return view('dashboard');
    })->name('dashboard');
    
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

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:PLATAFORMA_ADMIN'])
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('/businesses/{id}/approve', [DashboardController::class, 'approveBusiness'])
            ->whereNumber('id')
            ->name('businesses.approve');
        Route::post('/businesses/{id}/suspend', [DashboardController::class, 'suspendBusiness'])
            ->whereNumber('id')
            ->name('businesses.suspend');
        Route::post('/settings', [DashboardController::class, 'updateSettings'])->name('settings.update');
        Route::post('/failed-jobs/{id}/retry', [DashboardController::class, 'retryFailedJob'])
            ->whereNumber('id')
            ->name('jobs.retry');
    });

require __DIR__.'/auth.php';
