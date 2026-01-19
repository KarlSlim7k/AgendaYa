<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Business;
use App\Models\Employee;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Appointment>
 */
class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fechaHoraInicio = Carbon::now()->addDays(fake()->numberBetween(1, 30))
            ->setHour(fake()->numberBetween(9, 17))
            ->setMinute(fake()->randomElement([0, 15, 30, 45]))
            ->setSecond(0);

        return [
            'business_id' => Business::factory(),
            'user_id' => User::factory(),
            'employee_id' => Employee::factory(),
            'service_id' => Service::factory(),
            'fecha_hora_inicio' => $fechaHoraInicio,
            'fecha_hora_fin' => $fechaHoraInicio->copy()->addMinutes(30),
            'estado' => Appointment::ESTADO_PENDING,
            'notas_cliente' => fake()->optional()->sentence(),
            'notas_internas' => null,
            'motivo_cancelacion' => null,
            'custom_data' => null,
            'confirmada_en' => null,
            'completada_en' => null,
            'cancelada_en' => null,
            'cancelada_por_user_id' => null,
        ];
    }

    /**
     * Estado pendiente
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => Appointment::ESTADO_PENDING,
        ]);
    }

    /**
     * Estado confirmado
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => Appointment::ESTADO_CONFIRMED,
            'confirmada_en' => now(),
        ]);
    }

    /**
     * Estado completado
     */
    public function completed(): static
    {
        $pastDate = Carbon::now()->subDays(fake()->numberBetween(1, 30))
            ->setHour(fake()->numberBetween(9, 17))
            ->setMinute(0);

        return $this->state(fn (array $attributes) => [
            'estado' => Appointment::ESTADO_COMPLETED,
            'fecha_hora_inicio' => $pastDate,
            'fecha_hora_fin' => $pastDate->copy()->addMinutes(30),
            'confirmada_en' => $pastDate->copy()->subHours(24),
            'completada_en' => $pastDate->copy()->addMinutes(35),
        ]);
    }

    /**
     * Estado cancelado
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => Appointment::ESTADO_CANCELLED,
            'cancelada_en' => now(),
            'motivo_cancelacion' => fake()->sentence(),
        ]);
    }

    /**
     * Estado no-show
     */
    public function noShow(): static
    {
        $pastDate = Carbon::now()->subDays(fake()->numberBetween(1, 30))
            ->setHour(fake()->numberBetween(9, 17))
            ->setMinute(0);

        return $this->state(fn (array $attributes) => [
            'estado' => Appointment::ESTADO_NO_SHOW,
            'fecha_hora_inicio' => $pastDate,
            'fecha_hora_fin' => $pastDate->copy()->addMinutes(30),
            'confirmada_en' => $pastDate->copy()->subHours(24),
        ]);
    }

    /**
     * Cita para hoy
     */
    public function today(): static
    {
        $hora = Carbon::now()->setHour(fake()->numberBetween(9, 17))
            ->setMinute(fake()->randomElement([0, 15, 30, 45]))
            ->setSecond(0);

        return $this->state(fn (array $attributes) => [
            'fecha_hora_inicio' => $hora,
            'fecha_hora_fin' => $hora->copy()->addMinutes(30),
        ]);
    }

    /**
     * Cita para mañana
     */
    public function tomorrow(): static
    {
        $hora = Carbon::tomorrow()->setHour(fake()->numberBetween(9, 17))
            ->setMinute(fake()->randomElement([0, 15, 30, 45]))
            ->setSecond(0);

        return $this->state(fn (array $attributes) => [
            'fecha_hora_inicio' => $hora,
            'fecha_hora_fin' => $hora->copy()->addMinutes(30),
        ]);
    }

    /**
     * Cita en fecha/hora específica
     */
    public function at(Carbon $fechaHora, int $duracionMinutos = 30): static
    {
        return $this->state(fn (array $attributes) => [
            'fecha_hora_inicio' => $fechaHora,
            'fecha_hora_fin' => $fechaHora->copy()->addMinutes($duracionMinutos),
        ]);
    }
}
