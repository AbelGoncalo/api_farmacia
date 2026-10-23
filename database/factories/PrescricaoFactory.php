<?php

namespace Database\Factories;

use App\Models\Prescricao;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prescricao>
 */
class PrescricaoFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'id' => (string) fake()->uuid(),
            'utilizador_id' => User::factory(),
            'caminho_ficheiro' => 'prescricoes/'.fake()->uuid().'.pdf',
            'tipo_ficheiro' => 'PDF',
            'estado' => 'PENDENTE',
        ];
    }
}
