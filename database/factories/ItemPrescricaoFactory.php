<?php

namespace Database\Factories;

use App\Models\ItemPrescricao;
use App\Models\Prescricao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItemPrescricao>
 */
class ItemPrescricaoFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'id' => (string) fake()->uuid(),
            'prescricao_id' => Prescricao::factory(),
            'texto_original' => fake()->sentence(6),
            'nome_medicamento' => fake()->words(3, true),
            'dosagem' => fake()->randomElement(['500 mg', '10 mg', null]),
            'apresentacao' => fake()->randomElement(['Comprimidos', 'Cápsulas', null]),
            'quantidade' => fake()->randomFloat(3, 1, 60),
            'produto_id' => null,
            'estado_correspondencia' => 'PENDENTE',
            'metodo_correspondencia' => null,
            'confianca_correspondencia' => null,
        ];
    }
}
