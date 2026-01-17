<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tabla principal de negocios (tenants del sistema).
     * Cada negocio es un tenant independiente con sus propias sucursales, servicios, empleados.
     */
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('razon_social', 200)->nullable()->comment('Para facturación');
            $table->string('rfc', 13)->nullable()->comment('RFC mexicano (12-13 caracteres)');
            $table->string('telefono', 20)->comment('Formato +52 para México');
            $table->string('email', 150);
            $table->string('categoria', 50)->comment('peluqueria, clinica, taller, etc.');
            $table->text('descripcion')->nullable();
            $table->text('logo_url')->nullable();
            $table->enum('estado', ['pending', 'approved', 'suspended', 'inactive'])
                ->default('pending')
                ->comment('pending: pendiente aprobación, approved: activo, suspended: suspendido, inactive: inactivo');
            $table->json('meta')->nullable()->comment('Configuración adicional del negocio');
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('email');
            $table->index('telefono');
            $table->index('categoria');
            $table->index('estado');
            $table->index('created_at');
            
            // Índice único para RFC (si se proporciona)
            $table->unique('rfc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
