<?php

namespace Database\Factories;

use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BusinessLocation>
 */
class BusinessLocationFactory extends Factory
{
    private const COLONIAS_CDMX = [
        'Polanco', 'Condesa', 'Roma Norte', 'Roma Sur', 'Del Valle', 'Narvarte',
        'Coyoacán Centro', 'San Ángel', 'Napoles', 'Insurgentes', 'Juárez',
        'Centro Histórico', 'Santa Fe', 'Lindavista', 'Anáhuac', 'Anzures'
    ];

    private const CIUDADES = [
        ['ciudad' => 'Ciudad de México', 'estado' => 'CDMX', 'cp' => '06700'],
        ['ciudad' => 'Guadalajara', 'estado' => 'Jalisco', 'cp' => '44100'],
        ['ciudad' => 'Monterrey', 'estado' => 'Nuevo León', 'cp' => '64000'],
        ['ciudad' => 'Puebla', 'estado' => 'Puebla', 'cp' => '72000'],
        ['ciudad' => 'Querétaro', 'estado' => 'Querétaro', 'cp' => '76000'],
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $ciudad = fake()->randomElement(self::CIUDADES);
        $colonia = fake()->randomElement(self::COLONIAS_CDMX);
        $calle = fake()->streetName();
        $numero = fake()->buildingNumber();

        return [
            'business_id' => Business::factory(),
            'nombre' => fake()->randomElement(['Sucursal Centro', 'Sucursal Norte', 'Sucursal Sur', 'Matriz', 'Sucursal ' . $colonia]),
            'direccion' => "{$calle} #{$numero}, Col. {$colonia}",
            'ciudad' => $ciudad['ciudad'],
            'estado' => $ciudad['estado'],
            'codigo_postal' => $ciudad['cp'],
            'telefono' => '+52' . fake()->numerify('##########'),
            'zona_horaria' => 'America/Mexico_City',
            'latitud' => fake()->latitude(19.3, 19.5), // Coordenadas aproximadas de CDMX
            'longitud' => fake()->longitude(-99.2, -99.0),
            'activo' => fake()->boolean(90), // 90% activas
            'meta' => [
                'estacionamiento' => fake()->boolean(60),
                'accesible' => fake()->boolean(70),
                'wifi' => fake()->boolean(50),
            ],
        ];
    }

    /**
     * Sucursal activa.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'activo' => true,
        ]);
    }

    /**
     * Sucursal en Ciudad de México.
     */
    public function cdmx(): static
    {
        return $this->state(fn (array $attributes) => [
            'ciudad' => 'Ciudad de México',
            'estado' => 'CDMX',
            'codigo_postal' => '06700',
            'latitud' => fake()->latitude(19.3, 19.5),
            'longitud' => fake()->longitude(-99.2, -99.0),
        ]);
    }
}
