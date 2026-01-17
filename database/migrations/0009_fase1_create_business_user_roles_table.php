<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Asignación multi-tenant de roles a usuarios por negocio (con business_id).
     * Un usuario puede tener diferentes roles en diferentes negocios.
     * Ejemplo: Juan es STAFF en Negocio A y ADMIN en Negocio B.
     */
    public function up(): void
    {
        Schema::create('business_user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade');
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->onDelete('set null')
                ->comment('ID del usuario que asignó este rol');
            $table->timestamp('asignado_el')->useCurrent();
            $table->timestamps();
            $table->softDeletes();
            
            // Índices multi-tenant
            $table->index(['business_id', 'user_id']);
            $table->index(['business_id', 'role_id']);
            $table->index('user_id');
            $table->index('assigned_by');
            
            // Único compuesto - un usuario no puede tener el mismo rol duplicado en un negocio
            $table->unique(['user_id', 'business_id', 'role_id'], 'idx_business_user_roles_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_user_roles');
    }
};
