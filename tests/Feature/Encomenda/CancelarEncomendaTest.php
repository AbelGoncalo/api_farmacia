<?php

namespace Tests\Feature\Encomenda;

use App\Models\ItemPrescricao;
use App\Models\Prescricao;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\CriaEncomendas;
use Tests\TestCase;

class CancelarEncomendaTest extends TestCase
{
    use CriaEncomendas, RefreshDatabase;

    /**
     * Cria um produto sujeito a receita com stock e preço fixos.
     */
    private function produtoSujeitoAReceita(float $stock = 100.0): Produto
    {
        return Produto::factory()->create([
            'categoria_id' => null,
            'ativo' => true,
            'disponivel_online' => true,
            'requer_prescricao' => true,
            'quantidade_stock' => $stock,
            'preco' => 10.00,
        ]);
    }

    /**
     * Cria uma prescrição aprovada do utilizador com um item de receita
     * correspondente ao produto indicado.
     */
    private function prescricaoAprovada(
        int $utilizadorId,
        Produto $produto,
        float $quantidadePrescrita,
    ): Prescricao {
        $prescricao = Prescricao::factory()->create([
            'utilizador_id' => $utilizadorId,
            'estado' => 'APROVADA',
            'validade_ate' => now()->addDays(30)->toDateString(),
        ]);

        ItemPrescricao::factory()->create([
            'prescricao_id' => $prescricao->id,
            'produto_id' => $produto->id,
            'quantidade' => $quantidadePrescrita,
            'quantidade_consumida' => 0,
            'estado_correspondencia' => 'CONFIRMADA',
        ]);

        return $prescricao;
    }

    /**
     * Insere um pagamento para a encomenda indicada.
     */
    private function pagamento(string $encomendaId, string $estado = 'PENDENTE'): void
    {
        \DB::table('pagamentos')->insert([
            'id' => (string) Str::uuid(),
            'encomenda_id' => $encomendaId,
            'metodo' => 'MBWAY',
            'estado' => $estado,
            'valor' => 10.00,
            'criado_em' => now(),
            'atualizado_em' => now(),
        ]);
    }

    /**
     * Insere uma entrega para a encomenda indicada.
     */
    private function entrega(string $encomendaId, string $estado = 'PENDENTE'): void
    {
        \DB::table('entregas')->insert([
            'id' => (string) Str::uuid(),
            'encomenda_id' => $encomendaId,
            'tipo' => 'RECOLHA_LOJA',
            'estado' => $estado,
            'criado_em' => now(),
            'atualizado_em' => now(),
        ]);
    }

    public function test_cliente_cancela_encomenda_propria_aguardando_pagamento(): void
    {
        $this->autenticarCliente();
        $produto = $this->produtoComStock(10.0);
        $id = $this->criarEncomenda($produto, 3);

        $this->patchJson("/api/v1/encomendas/{$id}/cancelar")
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.estado', 'CANCELADA');

        $this->assertEquals(10.0, (float) $produto->fresh()->quantidade_stock);
    }

    public function test_cancelamento_devolve_quantidade_a_prescricao_e_permite_recompra(): void
    {
        $utilizador = $this->autenticarCliente();
        $produto = $this->produtoSujeitoAReceita(100.0);
        $prescricao = $this->prescricaoAprovada($utilizador->id, $produto, 30.0);

        $id = $this->criarEncomenda($produto, 10, ['prescricao_id' => $prescricao->id]);

        $item = ItemPrescricao::query()
            ->where('prescricao_id', $prescricao->id)
            ->sole();

        $this->assertEquals(10.0, (float) $item->quantidade_consumida);

        $this->patchJson("/api/v1/encomendas/{$id}/cancelar")->assertStatus(200);

        $item->refresh();
        $this->assertEquals(0.0, (float) $item->quantidade_consumida);
        $this->assertEquals(30.0, (float) $item->quantidade_restante);
        $this->assertDatabaseCount('itens_encomenda_prescricao', 0);

        // A receita volta a estar disponível para uma nova compra.
        $this->criarEncomenda($produto, 20, ['prescricao_id' => $prescricao->id]);
        $this->assertEquals(20.0, (float) $item->fresh()->quantidade_consumida);
    }

    public function test_cancelamento_falha_pagamento_pendente_e_cancela_entrega(): void
    {
        $this->autenticarCliente();
        $produto = $this->produtoComStock(10.0);
        $id = $this->criarEncomenda($produto, 1);

        $this->pagamento($id, 'PENDENTE');
        $this->entrega($id, 'PENDENTE');

        $this->patchJson("/api/v1/encomendas/{$id}/cancelar")->assertStatus(200);

        $this->assertDatabaseHas('pagamentos', ['encomenda_id' => $id, 'estado' => 'FALHADO']);
        $this->assertDatabaseHas('entregas', ['encomenda_id' => $id, 'estado' => 'CANCELADA']);
    }

    public function test_cliente_nao_cancela_encomenda_de_outro_utilizador(): void
    {
        $this->autenticarCliente();
        $produto = $this->produtoComStock(10.0);
        $id = $this->criarEncomenda($produto, 1);

        $outro = User::factory()->create(['papel' => 'CLIENTE', 'estado' => 'ATIVO']);
        $this->actingAs($outro, 'api');

        $this->patchJson("/api/v1/encomendas/{$id}/cancelar")
            ->assertStatus(404)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('encomendas', ['id' => $id, 'estado' => 'AGUARDANDO_PAGAMENTO']);
    }

    public function test_cliente_nao_cancela_encomenda_ja_paga(): void
    {
        $this->autenticarCliente();
        $produto = $this->produtoComStock(10.0);
        $id = $this->criarEncomenda($produto, 1);

        $this->pagamento($id, 'CONFIRMADO');
        \DB::table('encomendas')->where('id', $id)->update(['estado' => 'PAGA']);

        $this->patchJson("/api/v1/encomendas/{$id}/cancelar")
            ->assertStatus(409)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('encomendas', ['id' => $id, 'estado' => 'PAGA']);
    }

    public function test_operador_cancela_encomenda_paga_com_reversao_de_stock_e_prescricao(): void
    {
        $utilizador = User::factory()->create(['papel' => 'CLIENTE', 'estado' => 'ATIVO']);
        $produto = $this->produtoSujeitoAReceita(100.0);
        $prescricao = $this->prescricaoAprovada($utilizador->id, $produto, 30.0);

        $this->actingAs($utilizador, 'api');
        $id = $this->criarEncomenda($produto, 10, ['prescricao_id' => $prescricao->id]);

        $this->pagamento($id, 'CONFIRMADO');
        $this->entrega($id, 'PENDENTE');
        \DB::table('encomendas')->where('id', $id)->update(['estado' => 'PAGA']);

        $this->autenticarOperador();

        $this->patchJson("/api/v1/encomendas/{$id}/cancelar")
            ->assertStatus(200)
            ->assertJsonPath('data.estado', 'CANCELADA');

        $this->assertEquals(100.0, (float) $produto->fresh()->quantidade_stock);

        $item = ItemPrescricao::query()
            ->where('prescricao_id', $prescricao->id)
            ->sole();

        $this->assertEquals(0.0, (float) $item->quantidade_consumida);
        $this->assertDatabaseCount('itens_encomenda_prescricao', 0);

        // Pagamento confirmado (já cobrado) não é alterado; só o pendente falha.
        $this->assertDatabaseHas('pagamentos', ['encomenda_id' => $id, 'estado' => 'CONFIRMADO']);
        $this->assertDatabaseHas('entregas', ['encomenda_id' => $id, 'estado' => 'CANCELADA']);
    }

    public function test_operador_nao_cancela_encomenda_em_preparacao(): void
    {
        $this->autenticarCliente();
        $produto = $this->produtoComStock(10.0);
        $id = $this->criarEncomenda($produto, 1);

        \DB::table('encomendas')->where('id', $id)->update(['estado' => 'EM_PREPARACAO']);

        $this->autenticarOperador();

        $this->patchJson("/api/v1/encomendas/{$id}/cancelar")
            ->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_nao_cancela_encomenda_inexistente(): void
    {
        $this->autenticarCliente();

        $this->patchJson('/api/v1/encomendas/'.Str::uuid().'/cancelar')
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_cancelamento_requer_autenticacao(): void
    {
        $this->patchJson('/api/v1/encomendas/'.Str::uuid().'/cancelar')->assertStatus(401);
    }
}
