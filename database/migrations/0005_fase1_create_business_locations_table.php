<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Sucursales de cada negocio (multi-tenant con business_id).
     * Un negocio puede tener múltiples sucursales/locaciones físicas.
     */
    public function up(): void
    {
        Schema::create('business_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade');
            $table->string('nombre', 150)->comment('Nombre de la sucursal');
            $table->string('direccion', 255);
            $table->string('ciudad', 100);
            $table->string('estado', 100);
            $table->string('codigo_postal', 10);
            $table->string('telefono', 20)->nullable();
            $table->string('zona_horaria', 50)->default('America/Mexico_City');
            $table->decimal('latitud', 10, 8)->nullable();
            $table->decimal('longitud', 11, 8)->nullable();
            $table->boolean('activo')->default(true);
            $table->json('meta')->nullable()->comment('Configuración adicional de la sucursal');
            $table->timestamps();
            $table->softDeletes();
            
            // Índices multi-tenant
            $table->index(['business_id', 'activo']);
            $table->index(['business_id', 'ciudad']);
            $table->index('zona_horaria');
            
            // Índice único compuesto por tenant
            $table->unique(['business_id', 'nombre'], 'idx_business_locations_unique_nombre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_locations');
    }
};
