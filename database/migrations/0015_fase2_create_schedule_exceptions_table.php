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
        Schema::create('schedule_exceptions', function (Blueprint $table) {
            $table->id();
            
            // Foreign Key
            $table->foreignId('business_location_id')
                ->constrained('business_locations')
                ->onDelete('cascade')
                ->comment('FK a sucursal');
            
            // Campos de excepción
            $table->date('fecha')->comment('Fecha de la excepción');
            $table->enum('tipo', ['feriado', 'vacaciones', 'cierre'])->comment('Tipo de excepción');
            $table->boolean('todo_el_dia')->default(true)->comment('Si aplica todo el día');
            $table->time('hora_inicio')->nullable()->comment('Hora inicio si no es todo el día');
            $table->time('hora_fin')->nullable()->comment('Hora fin si no es todo el día');
            $table->string('motivo', 255)->nullable()->comment('Motivo de la excepción');
            
            // Timestamps
            $table->timestamps();
            
            // Indexes
            $table->index(['business_location_id', 'fecha'], 'idx_schedule_exceptions_location_fecha');
            $table->index('fecha');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_exceptions');
    }
};
