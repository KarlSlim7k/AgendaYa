<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Public\BusinessController;
use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\AvailabilityController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\ScheduleController;
use App\Http\Controllers\Api\V1\ServiceController;
use App\Http\Controllers\Api\ReportsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Rutas públicas
Route::prefix('v1')->group(function () {
    // Autenticación móvil
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    
    // Búsqueda de negocios (público)
    Route::get('/businesses', [BusinessController::class, 'index']);
    Route::get('/businesses/{id}', [BusinessController::class, 'show']);
    Route::get('/businesses/{id}/services', [BusinessController::class, 'services']);
    
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
        // Logout
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/user/profile', [AuthController::class, 'profile']);
        

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

        // Reportes y métricas
        Route::prefix('business')->group(function () {
            Route::get('/dashboard', [ReportsController::class, 'dashboard']);
            Route::get('/reports/appointments', [ReportsController::class, 'appointments']);
            Route::get('/reports/services', [ReportsController::class, 'topServices']);
            Route::get('/reports/chart-data', [ReportsController::class, 'chartData']);
        });
    });
});
