<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Configuraciones globales de la plataforma (sin business_id).
     * Almacena settings generales como límites, precios, features habilitados.
     */
    public function up(): void
    {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 100)->unique()->comment('Identificador único del setting');
            $table->text('valor')->comment('Valor del setting (JSON si es complejo)');
            $table->string('tipo', 50)->comment('Tipo: string, integer, boolean, json');
            $table->text('descripcion')->nullable();
            $table->boolean('editable')->default(true)->comment('Si puede modificarse desde panel admin');
            $table->timestamps();
            
            // Índices
            $table->index('clave');
            $table->index('editable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
    }
};
