<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'user_account_id' => null, // Opcional
            'nombre' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'telefono' => '+52 ' . fake()->numerify('## #### ####'),
            'estado' => 'disponible',
        ];
    }

    /**
     * Indicate that the employee is not available.
     */
    public function unavailable(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'no_disponible',
        ]);
    }

    /**
     * Indicate that the employee is on vacation.
     */
    public function onVacation(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'vacaciones',
        ]);
    }

    /**
     * Indicate that the employee has a linked user account.
     */
    public function withUserAccount(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_account_id' => User::factory(),
        ]);
    }
}
