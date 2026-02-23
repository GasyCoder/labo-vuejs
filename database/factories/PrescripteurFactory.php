<?php

namespace Database\Factories;

use App\Models\Prescripteur;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Prescripteur>
 */
class PrescripteurFactory extends Factory
{
    protected $model = Prescripteur::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        $nomsTypiques = [
            'RAKOTO',
            'RANDRIA',
            'RABE',
            'RAZANA',
            'RATSIMA',
            'RAHARJA',
            'ANDRIANA',
            'RATSIRAKA',
            'RASOLOFO',
            'RANDRIANA',
            'RAFARA',
            'RAMANA',
            'RASOLO',
            'RAZAKA',
            'RABARY',
        ];

        $prenomsTypiques = [
            'Jean',
            'Marie',
            'Paul',
            'Hanta',
            'Michel',
            'Voahangy',
            'Heriniaina',
            'Ony',
            'Tsiky',
            'Hery',
            'Naina',
            'Fara',
            'Mamitiana',
            'Tantely',
            'Diary',
            'Miharisoa',
            'Harilala',
        ];

        return [
            'nom' => $this->faker->randomElement($nomsTypiques).$this->faker->randomElement(['MANANA', 'SOLA', 'VELO', 'ANDRIANA']),
            'prenom' => $this->faker->randomElement($prenomsTypiques),
            'telephone' => '+261 '.$this->faker->randomElement(['32', '33', '34']).' '.
                $this->faker->numerify('## ### ##'),
            'is_active' => $this->faker->boolean(85), // 85% de chance d'être actif
            'notes' => $this->faker->optional(0.7)->sentence(10), // 70% de chance d'avoir des notes
        ];
    }

    /**
     * Indicate that the prescripteur is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the prescripteur is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
