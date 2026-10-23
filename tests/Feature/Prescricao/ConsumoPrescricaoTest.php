<?php

namespace Tests\Feature\Prescricao;

use App\Models\ItemPrescricao;
use App\Models\Prescricao;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CriaEncomendas;
use Tests\TestCase;

class ConsumoPrescricaoTest extends TestCase
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

    public function test_checkout_com_produto_sujeito_a_receita_sem_prescricao_retorna_422(): void
    {
        $this->autenticarCliente();
        $produto = $this->produtoSujeitoAReceita();

        $this->adicionarAoCarrinho($produto, 1);

        $this->postJson('/api/v1/encomendas', ['tipo_entrega' => 'RECOLHA_LOJA'])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['success', 'message', 'errors' => ['prescricao_id']]);

        $this->assertDatabaseCount('encomendas', 0);
    }

    public function test_checkout_com_prescricao_aprovada_cria_encomenda_e_regista_consumo(): void
    {
        $utilizador = $this->autenticarCliente();
        $produto = $this->produtoSujeitoAReceita();
        $prescricao = $this->prescricaoAprovada($utilizador->id, $produto, 30.0);

        $this->adicionarAoCarrinho($produto, 5);

        $this->postJson('/api/v1/encomendas', [
            'tipo_entrega' => 'RECOLHA_LOJA',
            'prescricao_id' => $prescricao->id,
        ])
            ->assertStatus(201)
            ->assertJsonPath('data.estado', 'AGUARDANDO_PAGAMENTO');

        $item = ItemPrescricao::query()
            ->where('prescricao_id', $prescricao->id)
            ->sole();

        $this->assertEquals(5.0, (float) $item->quantidade_consumida);
        $this->assertEquals(25.0, (float) $item->quantidade_restante);
        $this->assertDatabaseCount('itens_encomenda_prescricao', 1);
    }

    public function test_quantidade_consumida_e_acumulada_entre_encomendas(): void
    {
        $utilizador = $this->autenticarCliente();
        $produto = $this->produtoSujeitoAReceita();
        $prescricao = $this->prescricaoAprovada($utilizador->id, $produto, 30.0);

        $this->criarEncomenda($produto, 5, ['prescricao_id' => $prescricao->id]);
        $this->criarEncomenda($produto, 10, ['prescricao_id' => $prescricao->id]);

        $item = ItemPrescricao::query()
            ->where('prescricao_id', $prescricao->id)
            ->sole();

        $this->assertEquals(15.0, (float) $item->quantidade_consumida);
        $this->assertEquals(15.0, (float) $item->quantidade_restante);
        $this->assertDatabaseCount('itens_encomenda_prescricao', 2);
    }

    public function test_checkout_rejeita_quantidade_superior_a_restante(): void
    {
        $utilizador = $this->autenticarCliente();
        $produto = $this->produtoSujeitoAReceita();
        $prescricao = $this->prescricaoAprovada($utilizador->id, $produto, 30.0);

        $this->criarEncomenda($produto, 25, ['prescricao_id' => $prescricao->id]);

        $this->adicionarAoCarrinho($produto, 10);

        $this->postJson('/api/v1/encomendas', [
            'tipo_entrega' => 'RECOLHA_LOJA',
            'prescricao_id' => $prescricao->id,
        ])
            ->assertStatus(409)
            ->assertJsonPath('success', false);

        $item = ItemPrescricao::query()
            ->where('prescricao_id', $prescricao->id)
            ->sole();

        // Nada foi consumido na tentativa que excedeu o disponível.
        $this->assertEquals(25.0, (float) $item->quantidade_consumida);
        $this->assertDatabaseCount('encomendas', 1);
    }

    public function test_checkout_rejeita_prescricao_nao_aprovada(): void
    {
        $utilizador = $this->autenticarCliente();
        $produto = $this->produtoSujeitoAReceita();

        $prescricao = Prescricao::factory()->create([
            'utilizador_id' => $utilizador->id,
            'estado' => 'PENDENTE',
            'validade_ate' => now()->addDays(30)->toDateString(),
        ]);

        $this->adicionarAoCarrinho($produto, 1);

        $this->postJson('/api/v1/encomendas', [
            'tipo_entrega' => 'RECOLHA_LOJA',
            'prescricao_id' => $prescricao->id,
        ])
            ->assertStatus(409)
            ->assertJsonPath('success', false);

        $this->assertDatabaseCount('encomendas', 0);
    }

    public function test_checkout_rejeita_prescricao_de_outro_utilizador(): void
    {
        $utilizador = $this->autenticarCliente();
        $produto = $this->produtoSujeitoAReceita();

        $outro = User::factory()->create(['papel' => 'CLIENTE', 'estado' => 'ATIVO']);
        $prescricao = $this->prescricaoAprovada($outro->id, $produto, 30.0);

        $this->adicionarAoCarrinho($produto, 1);

        $this->postJson('/api/v1/encomendas', [
            'tipo_entrega' => 'RECOLHA_LOJA',
            'prescricao_id' => $prescricao->id,
        ])
            ->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_checkout_rejeita_prescricao_expirada(): void
    {
        $utilizador = $this->autenticarCliente();
        $produto = $this->produtoSujeitoAReceita();

        $prescricao = Prescricao::factory()->create([
            'utilizador_id' => $utilizador->id,
            'estado' => 'APROVADA',
            'validade_ate' => now()->subDay()->toDateString(),
        ]);

        ItemPrescricao::factory()->create([
            'prescricao_id' => $prescricao->id,
            'produto_id' => $produto->id,
            'quantidade' => 30.0,
            'quantidade_consumida' => 0,
            'estado_correspondencia' => 'CONFIRMADA',
        ]);

        $this->adicionarAoCarrinho($produto, 1);

        $this->postJson('/api/v1/encomendas', [
            'tipo_entrega' => 'RECOLHA_LOJA',
            'prescricao_id' => $prescricao->id,
        ])
            ->assertStatus(409)
            ->assertJsonPath('success', false);

        $this->assertDatabaseCount('encomendas', 0);
    }

    public function test_checkout_rejeita_prescricao_que_nao_cobre_o_produto(): void
    {
        $utilizador = $this->autenticarCliente();
        $produto = $this->produtoSujeitoAReceita();
        $outroProduto = $this->produtoSujeitoAReceita();

        $prescricao = $this->prescricaoAprovada($utilizador->id, $outroProduto, 30.0);

        $this->adicionarAoCarrinho($produto, 1);

        $this->postJson('/api/v1/encomendas', [
            'tipo_entrega' => 'RECOLHA_LOJA',
            'prescricao_id' => $prescricao->id,
        ])
            ->assertStatus(409)
            ->assertJsonPath('success', false);

        $this->assertDatabaseCount('encomendas', 0);
    }

    public function test_prescricao_sem_quantidade_exige_zero_e_nao_bloqueia(): void
    {
        $utilizador = $this->autenticarCliente();
        $produto = $this->produtoSujeitoAReceita();

        $prescricao = Prescricao::factory()->create([
            'utilizador_id' => $utilizador->id,
            'estado' => 'APROVADA',
            'validade_ate' => now()->addDays(30)->toDateString(),
        ]);

        // Item de receita sem quantidade definida: não impõe limite.
        ItemPrescricao::factory()->create([
            'prescricao_id' => $prescricao->id,
            'produto_id' => $produto->id,
            'quantidade' => null,
            'quantidade_consumida' => 0,
            'estado_correspondencia' => 'CONFIRMADA',
        ]);

        $this->adicionarAoCarrinho($produto, 3);

        $this->postJson('/api/v1/encomendas', [
            'tipo_entrega' => 'RECOLHA_LOJA',
            'prescricao_id' => $prescricao->id,
        ])
            ->assertStatus(201)
            ->assertJsonPath('data.estado', 'AGUARDANDO_PAGAMENTO');
    }

    public function test_produto_sem_requerer_receita_nao_exige_prescricao(): void
    {
        $this->autenticarCliente();
        $produto = $this->produtoComStock(10.0);

        $this->criarEncomenda($produto, 2);

        $this->assertDatabaseCount('encomendas', 1);
        $this->assertDatabaseCount('itens_encomenda_prescricao', 0);
    }
}
