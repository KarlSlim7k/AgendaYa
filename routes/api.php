<?php

use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\AvailabilityController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\ScheduleController;
use App\Http\Controllers\Api\V1\ServiceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Rutas públicas
Route::prefix('v1')->group(function () {
    // Disponibilidad pública (sin autenticación)
    Route::get('/availability/slots', [AvailabilityController::class, 'slots']);
});

// Rutas protegidas con Sanctum
Route::middleware('auth:sanctum')->group(function () {
    // Usuario autenticado
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // API v1
    Route::prefix('v1')->group(function () {
        // Citas
        Route::apiResource('appointments', AppointmentController::class)->only(['index', 'store', 'show', 'update']);
        Route::patch('/appointments/{appointment}/cancel', [AppointmentController::class, 'cancel']);

        // Servicios
        Route::apiResource('services', ServiceController::class);

        // Empleados
        Route::apiResource('employees', EmployeeController::class);

        // Horarios de sucursales
        Route::prefix('locations/{location}')->group(function () {
            // Templates (horarios base)
            Route::get('/schedules', [ScheduleController::class, 'indexTemplates']);
            Route::post('/schedules', [ScheduleController::class, 'storeTemplate']);

            // Excepciones
            Route::get('/exceptions', [ScheduleController::class, 'indexExceptions']);
            Route::post('/exceptions', [ScheduleController::class, 'storeException']);
        });

        // Actualizar/eliminar templates y excepciones
        Route::put('/schedules/{template}', [ScheduleController::class, 'updateTemplate']);
        Route::delete('/exceptions/{exception}', [ScheduleController::class, 'destroyException']);
    });
});
