<?php

namespace Database\Factories;

use App\Models\Produto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Produto>
 */
class ProdutoFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'id' => (string) fake()->uuid(),
            'codigo' => strtoupper(fake()->unique()->bothify('??####')),
            'tipo' => 'FARMACO',
            'nome' => fake()->words(3, true),
            'categoria_id' => null,
            'preco' => fake()->randomFloat(2, 1, 100),
            'quantidade_stock' => fake()->randomFloat(3, 1, 500),
            'ativo' => true,
            'disponivel_online' => true,
            'requer_prescricao' => false,
        ];
    }
}
