<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Business>
 */
class BusinessFactory extends Factory
{
    private const CATEGORIAS = [
        'peluqueria', 'clinica', 'taller_mecanico', 'spa', 'consultorio',
        'gimnasio', 'restaurante', 'estetica', 'veterinaria', 'dental'
    ];

    private const NOMBRES_NEGOCIOS = [
        'peluqueria' => ['Estilos Modernos', 'Cortes y Peinados', 'Salón Elegante', 'Belleza Total', 'Tijeras de Oro'],
        'clinica' => ['Clínica San Rafael', 'Centro Médico del Valle', 'Salud Integral', 'Clínica Familiar', 'Consultas Médicas'],
        'taller_mecanico' => ['Taller Rodríguez', 'Mecánica Express', 'Auto Service', 'Taller El Experto', 'Mecánica Profesional'],
        'spa' => ['Spa Relax', 'Relajación Total', 'Spa & Wellness', 'Oasis de Paz', 'Spa Naturaleza'],
        'consultorio' => ['Consultorio Dr. Pérez', 'Consultas Especializadas', 'Centro de Consultas', 'Atención Médica', 'Salud y Bienestar'],
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categoria = fake()->randomElement(self::CATEGORIAS);
        $nombres = self::NOMBRES_NEGOCIOS[$categoria] ?? ['Negocio ' . fake()->company()];
        $nombre = fake()->randomElement($nombres);

        return [
            'nombre' => $nombre,
            'razon_social' => $nombre . ' S.A. de C.V.',
            'rfc' => strtoupper(fake()->bothify('???######???')), // RFC de 13 caracteres
            'telefono' => '+52' . fake()->numerify('##########'),
            'email' => strtolower(str_replace(' ', '', $nombre)) . '@' . fake()->freeEmailDomain(),
            'categoria' => $categoria,
            'descripcion' => fake()->optional(0.7)->sentence(12),
            'logo_url' => fake()->optional(0.4)->imageUrl(300, 300, 'business'),
            'estado' => fake()->randomElement(['pending', 'approved', 'suspended', 'inactive']),
            'meta' => [
                'horario_general' => '09:00-18:00',
                'dias_laborales' => ['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'],
                'requiere_deposito' => fake()->boolean(30),
            ],
        ];
    }

    /**
     * Estado aprobado.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'approved',
        ]);
    }

    /**
     * Estado pendiente.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'pending',
        ]);
    }
}
