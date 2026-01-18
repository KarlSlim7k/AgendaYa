<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Agrega columna current_business_id para multi-tenancy.
     * Permite a usuarios con roles en múltiples negocios seleccionar el contexto actual.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('current_business_id')
                ->nullable()
                ->after('email_verified_at')
                ->constrained('businesses')
                ->nullOnDelete()
                ->comment('Negocio actualmente seleccionado por usuario con múltiples roles');
            
            $table->index('current_business_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['current_business_id']);
            $table->dropColumn('current_business_id');
        });
    }
};
