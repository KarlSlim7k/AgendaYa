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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            
            // Foreign Keys
            $table->foreignId('business_id')
                ->constrained('businesses')
                ->onDelete('cascade')
                ->comment('FK al negocio (tenant)');
            
            // Campos principales
            $table->string('nombre', 255)->comment('Nombre del servicio');
            $table->text('descripcion')->nullable()->comment('Descripción del servicio');
            $table->decimal('precio', 10, 2)->default(0.00)->comment('Precio del servicio');
            $table->unsignedInteger('duracion_minutos')->default(30)->comment('Duración en minutos');
            
            // Buffers
            $table->unsignedInteger('buffer_pre_minutos')->default(0)->comment('Buffer antes de la cita');
            $table->unsignedInteger('buffer_post_minutos')->default(0)->comment('Buffer después de la cita');
            
            // Estado y opciones
            $table->boolean('requiere_confirmacion')->default(false)->comment('Si requiere confirmación manual');
            $table->boolean('activo')->default(true)->comment('Si el servicio está activo');
            
            // Metadatos personalizables
            $table->json('meta')->nullable()->comment('Metadatos adicionales (deposito, instrucciones, custom_fields)');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('business_id');
            $table->unique(['business_id', 'nombre'], 'idx_services_business_nombre');
            $table->index('created_at');
        });
        
        // CHECK constraints solo para MySQL/MariaDB (SQLite no soporta ALTER TABLE ADD CONSTRAINT)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE services ADD CONSTRAINT chk_services_precio CHECK (precio >= 0)');
            DB::statement('ALTER TABLE services ADD CONSTRAINT chk_services_duracion CHECK (duracion_minutos >= 15)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
