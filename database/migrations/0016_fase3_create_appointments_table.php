<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            
            // Foreign Keys
            $table->foreignId('business_id')
                ->constrained('businesses')
                ->onDelete('cascade')
                ->comment('FK al negocio (tenant)');
            
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('FK al usuario final que reservó');
            
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->onDelete('cascade')
                ->comment('FK al empleado que atenderá');
            
            $table->foreignId('service_id')
                ->constrained('services')
                ->onDelete('cascade')
                ->comment('FK al servicio reservado');
            
            // Campos principales de la cita
            $table->dateTime('fecha_hora_inicio')
                ->comment('Fecha y hora de inicio (UTC)');
            
            $table->dateTime('fecha_hora_fin')
                ->comment('Fecha y hora de fin (UTC)');
            
            $table->enum('estado', ['pending', 'confirmed', 'completed', 'cancelled', 'no_show'])
                ->default('pending')
                ->comment('Estado actual de la cita');
            
            // Datos adicionales
            $table->text('notas_cliente')->nullable()
                ->comment('Notas del cliente al momento de reservar');
            
            $table->text('notas_internas')->nullable()
                ->comment('Notas internas del negocio sobre la cita');
            
            $table->string('motivo_cancelacion')->nullable()
                ->comment('Razón de cancelación si aplica');
            
            $table->json('custom_data')->nullable()
                ->comment('Datos personalizados según custom_fields del servicio');
            
            // Metadatos de gestión
            $table->timestamp('confirmada_en')->nullable()
                ->comment('Timestamp cuando se confirmó la cita');
            
            $table->timestamp('completada_en')->nullable()
                ->comment('Timestamp cuando se completó la cita');
            
            $table->timestamp('cancelada_en')->nullable()
                ->comment('Timestamp cuando se canceló');
            
            $table->foreignId('cancelada_por_user_id')->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->comment('Usuario que canceló (puede ser el cliente o admin)');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Índices críticos para performance y prevención de doble booking
            
            // Multi-tenant: SIEMPRE indexar business_id + campos de búsqueda
            $table->index(['business_id', 'employee_id', 'fecha_hora_inicio', 'estado'], 
                'idx_appointments_availability');
            
            // Prevención de doble booking - crítico para queries con lock
            $table->index(['employee_id', 'fecha_hora_inicio'], 
                'idx_appointments_employee_date');
            
            // Búsqueda de citas por usuario
            $table->index(['user_id', 'fecha_hora_inicio'], 
                'idx_appointments_user_date');
            
            // Búsqueda por estado (para reportes y dashboards)
            $table->index(['business_id', 'estado', 'fecha_hora_inicio'], 
                'idx_appointments_business_status');
            
            // Index para queries por fecha
            $table->index('fecha_hora_inicio');
            $table->index('created_at');
        });
        
        // CHECK constraints solo para MySQL/MariaDB
        if (config('database.default') !== 'sqlite') {
            DB::statement('ALTER TABLE appointments ADD CONSTRAINT chk_appointments_fecha_fin 
                CHECK (fecha_hora_fin > fecha_hora_inicio)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
