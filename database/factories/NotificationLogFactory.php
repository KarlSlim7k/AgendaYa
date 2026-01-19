<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NotificationLog>
 */
class NotificationLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'business_id' => \App\Models\Business::factory(),
            'appointment_id' => \App\Models\Appointment::factory(),
            'tipo' => fake()->randomElement(['email', 'whatsapp', 'sms']),
            'evento' => fake()->randomElement(['confirmacion', 'recordatorio_24h', 'recordatorio_1h', 'cancelacion', 'reprogramacion']),
            'estado' => fake()->randomElement(['enviado', 'fallido', 'reintentado']),
            'intentos' => fake()->numberBetween(1, 3),
            'ultimo_intento' => now(),
            'metadata' => [
                'to' => fake()->email(),
                'subject' => 'Test notification',
            ],
        ];
    }
}
