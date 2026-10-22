<?php

namespace Tests\Feature\Catalogo;

use App\Models\Categoria;
use App\Models\Produto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ValidacaoCatalogoTest extends TestCase
{
    use RefreshDatabase;

    public function test_listar_produtos_valida_parametros_de_query(): void
    {
        $this->getJson('/api/v1/catalogo/produtos?por_pagina=999&tipo=INVALIDO&categoria_id=nao-uuid')
            ->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonStructure(['success', 'message', 'errors' => ['categoria_id', 'tipo', 'por_pagina']]);
    }

    public function test_listar_produtos_com_query_valida_retorna_200(): void
    {
        $this->getJson('/api/v1/catalogo/produtos?pesquisa=ibuprofeno&por_pagina=10')
            ->assertStatus(200)
            ->assertJsonPath('meta.por_pagina', 10);
    }

    public function test_buscar_produto_com_uuid_invalido_retorna_422(): void
    {
        $this->getJson('/api/v1/catalogo/produtos/nao-e-uuid')
            ->assertStatus(422)
            ->assertJsonPath('message', 'O produto indicado não é válido.');
    }

    public function test_buscar_produto_inexistente_retorna_404(): void
    {
        $id = (string) Str::uuid();

        $this->getJson("/api/v1/catalogo/produtos/{$id}")
            ->assertStatus(404)
            ->assertJsonPath('message', 'Produto não encontrado.');
    }

    public function test_buscar_produto_existente_retorna_200(): void
    {
        $categoria = Categoria::factory()->create();
        $produto = Produto::factory()->create([
            'categoria_id' => $categoria->id,
            'ativo' => true,
            'disponivel_online' => true,
            'quantidade_stock' => 10,
        ]);

        $this->getJson("/api/v1/catalogo/produtos/{$produto->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $produto->id);
    }

    public function test_listar_produtos_filtra_por_categoria_inexistente(): void
    {
        $id = (string) Str::uuid();

        $this->getJson("/api/v1/catalogo/produtos?categoria_id={$id}")
            ->assertStatus(422)
            ->assertJsonPath('errors.categoria_id.0', 'A categoria indicada não existe.');
    }
}
