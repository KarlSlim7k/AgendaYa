<?php

use App\Http\Controllers\ProfileController;
use App\Livewire\Appointments\AppointmentsList;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Rutas de Citas (Livewire)
    Route::get('/appointments', AppointmentsList::class)->name('appointments.index');
    Route::get('/appointments/create', function () {
        return view('appointments.create');
    })->name('appointments.create');
    
    // Ruta de Gestión de Horarios
    Route::get('/schedules', function () {
        return view('schedules.index');
    })->name('schedules.index');
});

require __DIR__.'/auth.php';
