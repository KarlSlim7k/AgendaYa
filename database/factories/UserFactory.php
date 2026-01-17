<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Nombres mexicanos comunes.
     */
    private const NOMBRES_MASCULINOS = [
        'Juan', 'José', 'Miguel', 'Luis', 'Carlos', 'Pedro', 'Javier', 'Francisco',
        'Alejandro', 'Antonio', 'Ricardo', 'Fernando', 'Roberto', 'Daniel', 'Jorge',
        'Manuel', 'Eduardo', 'Arturo', 'Sergio', 'Rafael', 'Oscar', 'Raúl'
    ];

    private const NOMBRES_FEMENINOS = [
        'María', 'Guadalupe', 'Rosa', 'Ana', 'Patricia', 'Laura', 'Sofía', 'Carmen',
        'Leticia', 'Mariana', 'Daniela', 'Gabriela', 'Fernanda', 'Alejandra', 'Diana',
        'Karla', 'Paola', 'Isabel', 'Teresa', 'Elena', 'Claudia', 'Verónica'
    ];

    private const APELLIDOS = [
        'García', 'Martínez', 'López', 'González', 'Hernández', 'Rodríguez', 'Pérez',
        'Sánchez', 'Ramírez', 'Cruz', 'Flores', 'Gómez', 'Morales', 'Vázquez',
        'Jiménez', 'Reyes', 'Díaz', 'Torres', 'Gutiérrez', 'Ruiz', 'Mendoza', 'Álvarez'
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $esFemenino = fake()->boolean();
        $nombre = $esFemenino 
            ? fake()->randomElement(self::NOMBRES_FEMENINOS)
            : fake()->randomElement(self::NOMBRES_MASCULINOS);
        
        $apellidoPaterno = fake()->randomElement(self::APELLIDOS);
        $apellidoMaterno = fake()->randomElement(self::APELLIDOS);
        $apellidos = "{$apellidoPaterno} {$apellidoMaterno}";

        return [
            'nombre' => $nombre,
            'apellidos' => $apellidos,
            'email' => fake()->unique()->safeEmail(),
            'telefono' => '+52' . fake()->numerify('##########'), // +52 + 10 dígitos
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'foto_perfil_url' => fake()->optional(0.3)->imageUrl(200, 200, 'people'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
