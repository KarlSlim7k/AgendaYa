<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Catálogo global de roles del sistema (sin business_id).
     * 5 roles jerárquicos: USUARIO_FINAL, NEGOCIO_STAFF, NEGOCIO_MANAGER, NEGOCIO_ADMIN, PLATAFORMA_ADMIN
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique()->comment('USUARIO_FINAL, NEGOCIO_STAFF, etc.');
            $table->string('display_name', 150)->comment('Nombre legible para UI');
            $table->text('descripcion')->nullable();
            $table->integer('nivel_jerarquia')->comment('0=Usuario, 1=Staff, 2=Manager, 3=Admin, 4=Platform');
            $table->timestamps();
            
            // Índices
            $table->index('nombre');
            $table->index('nivel_jerarquia');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
