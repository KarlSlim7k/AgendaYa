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
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('tipo', ['email', 'whatsapp', 'sms'])->comment('Canal de notificación');
            $table->enum('evento', ['confirmacion', 'recordatorio_24h', 'recordatorio_1h', 'cancelacion', 'reprogramacion'])->comment('Tipo de evento');
            $table->enum('estado', ['enviado', 'fallido', 'reintentado'])->default('enviado')->comment('Estado del envío');
            $table->unsignedTinyInteger('intentos')->default(1)->comment('Número de intentos de envío');
            $table->timestamp('ultimo_intento')->useCurrent()->comment('Timestamp del último intento');
            $table->json('metadata')->nullable()->comment('Datos adicionales del envío');
            $table->timestamps();

            $table->index(['business_id', 'created_at']);
            $table->index(['appointment_id', 'tipo']);
            $table->index(['estado', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
