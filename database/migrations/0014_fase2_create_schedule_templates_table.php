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
        Schema::create('schedule_templates', function (Blueprint $table) {
            $table->id();
            
            // Foreign Key
            $table->foreignId('business_location_id')
                ->constrained('business_locations')
                ->onDelete('cascade')
                ->comment('FK a sucursal');
            
            // Campos de horario
            $table->unsignedTinyInteger('dia_semana')->comment('0=Domingo, 6=Sábado');
            $table->time('hora_apertura')->comment('Hora de apertura');
            $table->time('hora_cierre')->comment('Hora de cierre');
            $table->boolean('activo')->default(true)->comment('Si el día está activo');
            
            // Timestamps
            $table->timestamps();
            
            // Indexes
            $table->index('business_location_id');
            $table->unique(['business_location_id', 'dia_semana'], 'idx_schedule_templates_location_dia');
        });
        
        // CHECK constraints solo para MySQL/MariaDB
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE schedule_templates ADD CONSTRAINT chk_schedule_templates_dia CHECK (dia_semana BETWEEN 0 AND 6)');
            DB::statement('ALTER TABLE schedule_templates ADD CONSTRAINT chk_schedule_templates_horario CHECK (hora_cierre > hora_apertura)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_templates');
    }
};
