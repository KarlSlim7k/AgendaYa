<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'nombre' => fake()->words(3, true),
            'descripcion' => fake()->sentence(),
            'duracion_minutos' => fake()->randomElement([15, 30, 45, 60, 90, 120]),
            'precio' => fake()->randomFloat(2, 50, 1000),
            'buffer_pre_minutos' => fake()->randomElement([0, 5, 10, 15]),
            'buffer_post_minutos' => fake()->randomElement([0, 5, 10, 15]),
            'activo' => true,
        ];
    }

    /**
     * Indicate that the service is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'activo' => false,
        ]);
    }
}
