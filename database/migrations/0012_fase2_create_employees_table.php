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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            
            // Foreign Keys
            $table->foreignId('business_id')
                ->constrained('businesses')
                ->onDelete('cascade')
                ->comment('FK al negocio (tenant)');
            
            $table->foreignId('user_account_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->comment('FK opcional a users si tiene cuenta');
            
            // Datos del empleado
            $table->string('nombre', 255)->comment('Nombre del empleado');
            $table->string('email', 255)->nullable()->comment('Email del empleado');
            $table->string('telefono', 20)->nullable();
            $table->string('avatar_url', 500)->nullable();
            $table->string('cargo', 100)->nullable()->comment('Cargo o puesto');
            
            // Estado del empleado
            $table->enum('estado', ['disponible', 'no_disponible', 'vacaciones', 'baja'])
                ->default('disponible');
            
            // Metadatos
            $table->json('meta')->nullable()->comment('Datos adicionales del empleado');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('business_id');
            $table->unique(['business_id', 'email'], 'idx_employees_business_email');
            $table->index('user_account_id');
            $table->index(['business_id', 'deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
