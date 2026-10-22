<?php

namespace Database\Factories;

use App\Models\Categoria;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Categoria>
 */
class CategoriaFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'id' => (string) fake()->uuid(),
            'nome' => fake()->words(2, true),
            'descricao' => fake()->sentence(),
            'ativa' => true,
        ];
    }
}
