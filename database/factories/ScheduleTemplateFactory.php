<?php

namespace Database\Factories;

use App\Models\BusinessLocation;
use App\Models\ScheduleTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ScheduleTemplate>
 */
class ScheduleTemplateFactory extends Factory
{
    protected $model = ScheduleTemplate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_location_id' => BusinessLocation::factory(),
            'dia_semana' => fake()->numberBetween(0, 6),
            'hora_apertura' => '09:00',
            'hora_cierre' => '18:00',
            'activo' => true,
        ];
    }

    /**
     * Indicate that the schedule is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'activo' => false,
        ]);
    }

    /**
     * Set specific day of week.
     */
    public function forDay(int $dayOfWeek): static
    {
        return $this->state(fn (array $attributes) => [
            'dia_semana' => $dayOfWeek,
        ]);
    }
}
