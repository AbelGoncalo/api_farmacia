<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        User::factory()->create([
            'nome' => 'Utilizador de Teste',
            'email' => 'test@example.com',
        ]);

        $this->call(CatalogoDemonstracaoSeeder::class);
    }
}
