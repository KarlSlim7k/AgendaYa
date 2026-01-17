<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Catálogo global de permisos granulares (sin business_id).
     * 26 permisos con formato módulo.acción: perfil.read, negocio.update, servicio.create, etc.
     */
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique()->comment('Formato: módulo.acción');
            $table->string('display_name', 150)->comment('Nombre legible para UI');
            $table->text('descripcion')->nullable();
            $table->string('modulo', 50)->comment('perfil, negocio, sucursal, servicio, etc.');
            $table->string('accion', 50)->comment('read, create, update, delete');
            $table->timestamps();
            
            // Índices
            $table->index('nombre');
            $table->index('modulo');
            $table->index(['modulo', 'accion']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
