<?php

namespace Tests\Feature\Encomenda;

use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class CheckoutEncomendaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Cria um cliente e autentica-o no guard `api`.
     */
    private function autenticar(): User
    {
        $utilizador = User::factory()->create([
            'papel' => 'CLIENTE',
            'estado' => 'ATIVO',
        ]);

        $this->actingAs($utilizador, 'api');

        return $utilizador;
    }

    /**
     * Cria um produto disponível com o stock indicado e preço fixo.
     */
    private function produtoComStock(float $stock): Produto
    {
        return Produto::factory()->create([
            'categoria_id' => null,
            'ativo' => true,
            'disponivel_online' => true,
            'quantidade_stock' => $stock,
            'preco' => 10.00,
        ]);
    }

    /**
     * Insere uma morada do utilizador na base de dados e devolve o respetivo id.
     */
    private function criarMorada(User $utilizador): string
    {
        $id = (string) Str::uuid();

        DB::table('moradas')->insert([
            'id' => $id,
            'utilizador_id' => $utilizador->id,
            'nome_destinatario' => 'Maria Silva',
            'telefone' => '912345678',
            'provincia' => 'Lisboa',
            'municipio' => 'Lisboa',
            'bairro' => 'Centro',
            'predefinida' => true,
            'ativa' => true,
            'criado_em' => now(),
            'atualizado_em' => now(),
        ]);

        return $id;
    }

    /**
     * Adiciona uma quantidade de um produto ao carrinho do utilizador autenticado.
     */
    private function adicionarAoCarrinho(Produto $produto, int $quantidade): void
    {
        $this->postJson('/api/v1/carrinho/itens', [
            'produto_id' => $produto->id,
            'quantidade' => $quantidade,
        ])->assertStatus(201);
    }

    public function test_checkout_cria_encomenda_decrementa_stock_e_esvazia_carrinho(): void
    {
        $this->autenticar();
        $produto = $this->produtoComStock(10.0);

        $this->adicionarAoCarrinho($produto, 3);

        $this->postJson('/api/v1/encomendas', ['tipo_entrega' => 'RECOLHA_LOJA'])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.estado', 'AGUARDANDO_PAGAMENTO')
            ->assertJsonPath('data.tipo_entrega', 'RECOLHA_LOJA')
            ->assertJsonPath('data.subtotal', '30.00')
            ->assertJsonPath('data.taxa_entrega', '0.00')
            ->assertJsonPath('data.total', '30.00')
            ->assertJsonPath('data.itens.0.quantidade', '3.000')
            ->assertJsonPath('data.itens.0.preco_unitario', '10.00');

        $this->assertEquals(7.0, (float) $produto->fresh()->quantidade_stock);
        $this->assertDatabaseCount('itens_carrinho', 0);
    }

    public function test_checkout_com_carrinho_vazio_retorna_422(): void
    {
        $this->autenticar();

        $this->postJson('/api/v1/encomendas', ['tipo_entrega' => 'RECOLHA_LOJA'])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'O carrinho está vazio.');
    }

    public function test_checkout_rejeita_stock_insuficiente(): void
    {
        $this->autenticar();
        $produto = $this->produtoComStock(10.0);

        $this->adicionarAoCarrinho($produto, 5);

        // Simula uma redução concorrente do stock depois de o item entrar no carrinho.
        $produto->update(['quantidade_stock' => 2]);

        $this->postJson('/api/v1/encomendas', ['tipo_entrega' => 'RECOLHA_LOJA'])
            ->assertStatus(409)
            ->assertJsonPath('success', false);

        $this->assertEquals(2.0, (float) $produto->fresh()->quantidade_stock);
        $this->assertDatabaseCount('encomendas', 0);
    }

    public function test_checkout_valida_tipo_entrega(): void
    {
        $this->autenticar();

        $this->postJson('/api/v1/encomendas', [])
            ->assertStatus(422)
            ->assertJsonStructure(['success', 'message', 'errors' => ['tipo_entrega']]);
    }

    public function test_checkout_entrega_domicilio_requer_morada(): void
    {
        $this->autenticar();

        $this->postJson('/api/v1/encomendas', ['tipo_entrega' => 'ENTREGA_DOMICILIO'])
            ->assertStatus(422)
            ->assertJsonStructure(['success', 'message', 'errors' => ['morada_id']]);
    }

    public function test_checkout_entrega_domicilio_aplica_taxa_configurada(): void
    {
        $utilizador = $this->autenticar();
        $moradaId = $this->criarMorada($utilizador);
        $produto = $this->produtoComStock(10.0);

        $this->adicionarAoCarrinho($produto, 2);

        $taxa = (float) config('encomendas.taxa_entrega_domicilio');

        $this->postJson('/api/v1/encomendas', [
            'tipo_entrega' => 'ENTREGA_DOMICILIO',
            'morada_id' => $moradaId,
        ])
            ->assertStatus(201)
            ->assertJsonPath('data.taxa_entrega', number_format($taxa, 2, '.', ''))
            ->assertJsonPath('data.total', number_format(20.0 + $taxa, 2, '.', ''));
    }

    public function test_index_lista_encomendas_do_utilizador(): void
    {
        $this->autenticar();
        $produto = $this->produtoComStock(10.0);

        $this->adicionarAoCarrinho($produto, 1);
        $this->postJson('/api/v1/encomendas', ['tipo_entrega' => 'RECOLHA_LOJA'])->assertStatus(201);

        $this->getJson('/api/v1/encomendas')
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.estado', 'AGUARDANDO_PAGAMENTO');
    }

    public function test_show_detalha_encomenda_propria(): void
    {
        $this->autenticar();
        $produto = $this->produtoComStock(10.0);

        $this->adicionarAoCarrinho($produto, 1);

        $id = $this->postJson('/api/v1/encomendas', ['tipo_entrega' => 'RECOLHA_LOJA'])
            ->assertStatus(201)
            ->json('data.id');

        $this->getJson("/api/v1/encomendas/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $id);
    }

    public function test_show_nao_acede_a_encomenda_de_outro_utilizador(): void
    {
        $this->autenticar();
        $produto = $this->produtoComStock(10.0);

        $this->adicionarAoCarrinho($produto, 1);

        $id = $this->postJson('/api/v1/encomendas', ['tipo_entrega' => 'RECOLHA_LOJA'])
            ->assertStatus(201)
            ->json('data.id');

        $outro = User::factory()->create(['papel' => 'CLIENTE', 'estado' => 'ATIVO']);
        $this->actingAs($outro, 'api');

        $this->getJson("/api/v1/encomendas/{$id}")->assertStatus(404);
    }

    public function test_encomendas_requerem_autenticacao(): void
    {
        $this->postJson('/api/v1/encomendas', ['tipo_entrega' => 'RECOLHA_LOJA'])->assertStatus(401);
        $this->getJson('/api/v1/encomendas')->assertStatus(401);
    }
}
