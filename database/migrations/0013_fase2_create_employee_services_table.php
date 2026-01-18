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
        Schema::create('employee_services', function (Blueprint $table) {
            $table->id();
            
            // Foreign Keys
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->onDelete('cascade');
            
            $table->foreignId('service_id')
                ->constrained('services')
                ->onDelete('cascade');
            
            // Timestamp
            $table->timestamp('created_at')->useCurrent();
            
            // Indexes
            $table->unique(['employee_id', 'service_id'], 'idx_employee_services_unique');
            $table->index('employee_id');
            $table->index('service_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_services');
    }
};
