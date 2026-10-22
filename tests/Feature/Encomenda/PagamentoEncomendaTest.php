<?php

namespace Tests\Feature\Encomenda;

use App\Models\Encomenda;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\CriaEncomendas;
use Tests\TestCase;

class PagamentoEncomendaTest extends TestCase
{
    use CriaEncomendas, RefreshDatabase;

    /**
     * Executa o fluxo completo até criar um pagamento pendente e devolve o seu id.
     */
    private function criarPagamentoPendente(): string
    {
        $this->autenticarCliente();
        $produto = $this->produtoComStock(10.0);
        $encomendaId = $this->criarEncomenda($produto, 2);

        return $this->postJson("/api/v1/encomendas/{$encomendaId}/pagamento", [
            'metodo' => 'MULTIBANCO',
            'referencia' => 'REF-123',
        ])
            ->assertStatus(201)
            ->json('data.id');
    }

    public function test_cliente_regista_pagamento_pendente_para_a_sua_encomenda(): void
    {
        $this->autenticarCliente();
        $produto = $this->produtoComStock(10.0);
        $encomendaId = $this->criarEncomenda($produto, 2);

        $this->postJson("/api/v1/encomendas/{$encomendaId}/pagamento", [
            'metodo' => 'MULTIBANCO',
            'referencia' => 'REF-123',
        ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.metodo', 'MULTIBANCO')
            ->assertJsonPath('data.estado', 'PENDENTE')
            ->assertJsonPath('data.valor', '20.00')
            ->assertJsonPath('data.referencia', 'REF-123');

        // A encomenda continua a aguardar pagamento até à confirmação.
        $this->assertSame('AGUARDANDO_PAGAMENTO', Encomenda::find($encomendaId)->estado);
    }

    public function test_valor_do_pagamento_e_o_total_da_encomenda(): void
    {
        // Encomenda com entrega ao domicílio: total inclui a taxa configurada.
        $utilizador = $this->autenticarCliente();
        $moradaId = $this->criarMorada($utilizador);
        $produto = $this->produtoComStock(10.0);
        $encomendaId = $this->criarEncomenda($produto, 1, [
            'tipo_entrega' => 'ENTREGA_DOMICILIO',
            'morada_id' => $moradaId,
        ]);

        $taxa = (float) config('encomendas.taxa_entrega_domicilio');

        // O valor é sempre o total da encomenda, calculado no servidor.
        $this->postJson("/api/v1/encomendas/{$encomendaId}/pagamento", ['metodo' => 'NUMERARIO'])
            ->assertStatus(201)
            ->assertJsonPath('data.valor', number_format(10.0 + $taxa, 2, '.', ''));
    }

    public function test_nao_e_possivel_registar_dois_pagamentos_pendentes(): void
    {
        $this->criarPagamentoPendente();

        $encomendaId = Encomenda::first()->id;

        $this->postJson("/api/v1/encomendas/{$encomendaId}/pagamento", ['metodo' => 'TPA'])
            ->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_cliente_nao_regista_pagamento_de_encomenda_de_outro_utilizador(): void
    {
        $this->autenticarCliente();
        $produto = $this->produtoComStock(10.0);
        $encomendaId = $this->criarEncomenda($produto);

        $outro = User::factory()->create(['papel' => 'CLIENTE', 'estado' => 'ATIVO']);
        $this->actingAs($outro, 'api');

        $this->postJson("/api/v1/encomendas/{$encomendaId}/pagamento", ['metodo' => 'TPA'])
            ->assertStatus(404);
    }

    public function test_pagamento_e_rejeitado_quando_a_encomenda_ja_foi_paga(): void
    {
        $utilizador = $this->autenticarCliente();
        $produto = $this->produtoComStock(10.0);
        $encomendaId = $this->criarEncomenda($produto);
        $pagamentoId = $this->criarPagamentoPendentePara($encomendaId);

        $this->autenticarOperador();
        $this->confirmarPagamento($pagamentoId)->assertStatus(200);

        $this->actingAs($utilizador, 'api');
        $this->postJson("/api/v1/encomendas/{$encomendaId}/pagamento", ['metodo' => 'TPA'])
            ->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_registo_de_pagamento_valida_metodo(): void
    {
        $this->autenticarCliente();
        $produto = $this->produtoComStock(10.0);
        $encomendaId = $this->criarEncomenda($produto);

        $this->postJson("/api/v1/encomendas/{$encomendaId}/pagamento", ['metodo' => 'BITCOIN'])
            ->assertStatus(422)
            ->assertJsonStructure(['success', 'message', 'errors' => ['metodo']]);
    }

    public function test_operador_confirma_pagamento_e_encomenda_fica_paga(): void
    {
        $pagamentoId = $this->criarPagamentoPendente();
        $encomendaId = Encomenda::first()->id;

        $this->autenticarOperador();

        $resposta = $this->confirmarPagamento($pagamentoId, [
            'id_transacao' => 'TXN-999',
            'dados_resposta_operador' => ['nota' => 'Pago no balcão'],
        ])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.estado', 'CONFIRMADO')
            ->assertJsonPath('data.id_transacao', 'TXN-999');

        $this->assertNotNull($resposta->json('data.pago_em'));
        $this->assertSame('PAGA', Encomenda::find($encomendaId)->estado);
    }

    public function test_confirmacao_e_restrita_ao_gate_processar_encomendas(): void
    {
        $pagamentoId = $this->criarPagamentoPendente();

        // Um cliente não tem o gate `processar-encomendas`.
        $this->actingAs(User::factory()->create(['papel' => 'CLIENTE', 'estado' => 'ATIVO']), 'api');

        $this->confirmarPagamento($pagamentoId)->assertStatus(403);
    }

    public function test_confirmar_pagamento_inexistente_retorna_404(): void
    {
        $this->autenticarOperador();

        $this->confirmarPagamento((string) Str::uuid())->assertStatus(404);
    }

    public function test_nao_e_possivel_confirmar_pagamento_duas_vezes(): void
    {
        $pagamentoId = $this->criarPagamentoPendente();

        $this->autenticarOperador();

        $this->confirmarPagamento($pagamentoId)->assertStatus(200);
        $this->confirmarPagamento($pagamentoId)
            ->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_detalhe_da_encomenda_inclui_pagamentos(): void
    {
        $this->criarPagamentoPendente();
        $encomendaId = Encomenda::first()->id;

        $this->getJson("/api/v1/encomendas/{$encomendaId}")
            ->assertStatus(200)
            ->assertJsonPath('data.pagamentos.0.estado', 'PENDENTE')
            ->assertJsonPath('data.pagamentos.0.metodo', 'MULTIBANCO');
    }

    public function test_fluxo_completo_pagamento_requer_autenticacao(): void
    {
        $this->postJson('/api/v1/encomendas/'.Str::uuid().'/pagamento', ['metodo' => 'TPA'])
            ->assertStatus(401);
        $this->patchJson('/api/v1/pagamentos/'.Str::uuid().'/confirmar')
            ->assertStatus(401);
    }

    /**
     * Cria um pagamento pendente para a encomenda indicada e devolve o seu id.
     */
    private function criarPagamentoPendentePara(string $encomendaId): string
    {
        return $this->postJson("/api/v1/encomendas/{$encomendaId}/pagamento", [
            'metodo' => 'MULTIBANCO',
        ])
            ->assertStatus(201)
            ->json('data.id');
    }

    /**
     * Envia o pedido de confirmação de um pagamento como operador.
     *
     * @param  array<string, mixed>  $dados
     */
    private function confirmarPagamento(string $pagamentoId, array $dados = []): TestResponse
    {
        return $this->patchJson("/api/v1/pagamentos/{$pagamentoId}/confirmar", $dados);
    }
}
