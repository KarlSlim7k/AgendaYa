<?php

namespace Database\Factories;

use App\Models\BusinessLocation;
use App\Models\ScheduleException;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ScheduleException>
 */
class ScheduleExceptionFactory extends Factory
{
    protected $model = ScheduleException::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_location_id' => BusinessLocation::factory(),
            'fecha' => fake()->dateTimeBetween('+1 week', '+3 months')->format('Y-m-d'),
            'tipo' => fake()->randomElement(['feriado', 'vacaciones', 'cierre']),
            'motivo' => fake()->sentence(),
            'todo_el_dia' => true,
            'hora_inicio' => null,
            'hora_fin' => null,
        ];
    }

    /**
     * Indicate that the exception is partial (not all day).
     */
    public function partial(): static
    {
        return $this->state(fn (array $attributes) => [
            'todo_el_dia' => false,
            'hora_inicio' => '12:00',
            'hora_fin' => '14:00',
        ]);
    }

    /**
     * Set exception as holiday.
     */
    public function holiday(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => 'feriado',
        ]);
    }
}
