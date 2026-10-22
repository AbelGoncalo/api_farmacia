<?php

namespace Tests\Feature\Encomenda;

use App\Models\Encomenda;
use App\Models\Entrega;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\CriaEncomendas;
use Tests\TestCase;

class EntregaEncomendaTest extends TestCase
{
    use CriaEncomendas, RefreshDatabase;

    /** Cliente dono das encomendas criadas nos testes. */
    private User $cliente;

    /**
     * Cria uma encomenda de recolha na loja e devolve o id da entrega associada.
     */
    private function criarEntregaRecolha(): string
    {
        $this->cliente = $this->autenticarCliente();
        $produto = $this->produtoComStock(10.0);
        $encomendaId = $this->criarEncomenda($produto, 1);

        return $this->pagarEConfirmar($encomendaId);
    }

    /**
     * Cria uma encomenda ao domicílio e devolve o id da entrega associada.
     */
    private function criarEntregaDomicilio(): string
    {
        $this->cliente = $this->autenticarCliente();
        $moradaId = $this->criarMorada($this->cliente);
        $produto = $this->produtoComStock(10.0);
        $encomendaId = $this->criarEncomenda($produto, 1, [
            'tipo_entrega' => 'ENTREGA_DOMICILIO',
            'morada_id' => $moradaId,
        ]);

        return $this->pagarEConfirmar($encomendaId);
    }

    /**
     * Regista e confirma o pagamento de uma encomenda, devolvendo o id da entrega
     * criada. No final, volta a autenticar o cliente dono da encomenda.
     */
    private function pagarEConfirmar(string $encomendaId): string
    {
        $pagamentoId = $this->postJson("/api/v1/encomendas/{$encomendaId}/pagamento", [
            'metodo' => 'MULTIBANCO',
        ])
            ->assertStatus(201)
            ->json('data.id');

        $this->autenticarOperador();
        $this->patchJson("/api/v1/pagamentos/{$pagamentoId}/confirmar")->assertStatus(200);

        $this->actingAs($this->cliente, 'api');

        return Entrega::where('encomenda_id', $encomendaId)->firstOrFail()->id;
    }

    public function test_confirmacao_de_pagamento_cria_entrega_pendente(): void
    {
        $entregaId = $this->criarEntregaRecolha();

        $this->assertDatabaseHas('entregas', [
            'id' => $entregaId,
            'tipo' => 'RECOLHA_LOJA',
            'estado' => 'PENDENTE',
        ]);
    }

    public function test_entrega_a_domicilio_guarda_instantaneo_da_morada(): void
    {
        $entregaId = $this->criarEntregaDomicilio();

        $entrega = Entrega::find($entregaId);

        $this->assertSame('ENTREGA_DOMICILIO', $entrega->tipo);
        $this->assertSame('Maria Silva', $entrega->morada_historico['nome_destinatario']);
        $this->assertSame('Lisboa', $entrega->morada_historico['municipio']);
    }

    public function test_entrega_de_recolha_na_loja_nao_tem_morada(): void
    {
        $entregaId = $this->criarEntregaRecolha();

        $this->assertNull(Entrega::find($entregaId)->morada_historico);
    }

    public function test_detalhe_da_encomenda_inclui_entrega(): void
    {
        $entregaId = $this->criarEntregaRecolha();
        $encomendaId = Encomenda::first()->id;

        // O helper já deixa o cliente dono autenticado.
        $this->getJson("/api/v1/encomendas/{$encomendaId}")
            ->assertStatus(200)
            ->assertJsonPath('data.entrega.id', $entregaId)
            ->assertJsonPath('data.entrega.estado', 'PENDENTE');
    }

    public function test_operador_lista_entregas_com_filtro_de_estado(): void
    {
        $this->criarEntregaRecolha();

        $this->autenticarOperador();
        $this->getJson('/api/v1/entregas?estado=PENDENTE')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.estado', 'PENDENTE');

        $this->getJson('/api/v1/entregas?estado=ENTREGUE')
            ->assertStatus(200)
            ->assertJsonPath('data', []);
    }

    public function test_listagem_de_entregas_e_restrita_ao_gate_processar_encomendas(): void
    {
        $this->autenticarCliente();

        $this->getJson('/api/v1/entregas')->assertStatus(403);
    }

    public function test_operador_inicia_transporte_e_encomenda_fica_enviada(): void
    {
        $entregaId = $this->criarEntregaDomicilio();
        $encomendaId = Encomenda::first()->id;

        $this->autenticarOperador();
        $this->patchJson("/api/v1/entregas/{$entregaId}/iniciar-transporte")
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.estado', 'EM_TRANSPORTE');

        $this->assertSame('ENVIADA', Encomenda::find($encomendaId)->estado);
    }

    public function test_nao_e_possivel_iniciar_transporte_duas_vezes(): void
    {
        $entregaId = $this->criarEntregaDomicilio();

        $this->autenticarOperador();
        $this->patchJson("/api/v1/entregas/{$entregaId}/iniciar-transporte")->assertStatus(200);
        $this->patchJson("/api/v1/entregas/{$entregaId}/iniciar-transporte")
            ->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_transporte_nao_se_aplica_a_recolha_na_loja(): void
    {
        $entregaId = $this->criarEntregaRecolha();

        $this->autenticarOperador();
        $this->patchJson("/api/v1/entregas/{$entregaId}/iniciar-transporte")
            ->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_operador_confirma_entrega_a_domicilio_e_encomenda_fica_entregue(): void
    {
        $entregaId = $this->criarEntregaDomicilio();
        $encomendaId = Encomenda::first()->id;

        $this->autenticarOperador();
        $this->patchJson("/api/v1/entregas/{$entregaId}/iniciar-transporte")->assertStatus(200);

        $resposta = $this->patchJson("/api/v1/entregas/{$entregaId}/confirmar")
            ->assertStatus(200)
            ->assertJsonPath('data.estado', 'ENTREGUE');

        $this->assertNotNull($resposta->json('data.entregue_em'));
        $this->assertSame('ENTREGUE', Encomenda::find($encomendaId)->estado);
    }

    public function test_confirmar_entrega_a_domicilio_requer_transporte_iniciado(): void
    {
        $entregaId = $this->criarEntregaDomicilio();

        $this->autenticarOperador();
        $this->patchJson("/api/v1/entregas/{$entregaId}/confirmar")
            ->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_confirmacao_de_entrega_nao_se_aplica_a_recolha_na_loja(): void
    {
        $entregaId = $this->criarEntregaRecolha();

        $this->autenticarOperador();
        $this->patchJson("/api/v1/entregas/{$entregaId}/confirmar")
            ->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_operador_confirma_levantamento_e_encomenda_fica_entregue(): void
    {
        $entregaId = $this->criarEntregaRecolha();
        $encomendaId = Encomenda::first()->id;

        $this->autenticarOperador();
        $resposta = $this->patchJson("/api/v1/entregas/{$entregaId}/confirmar-levantamento")
            ->assertStatus(200)
            ->assertJsonPath('data.estado', 'ENTREGUE');

        $this->assertNotNull($resposta->json('data.levantamento_confirmado_em'));
        $this->assertSame('ENTREGUE', Encomenda::find($encomendaId)->estado);
    }

    public function test_confirmar_levantamento_requer_encomenda_paga(): void
    {
        // Encomenda criada mas sem pagamento: o pagamento é registado mas não confirmado.
        $this->autenticarCliente();
        $produto = $this->produtoComStock(10.0);
        $encomendaId = $this->criarEncomenda($produto, 1);
        $this->postJson("/api/v1/encomendas/{$encomendaId}/pagamento", ['metodo' => 'MULTIBANCO'])
            ->assertStatus(201);

        // Sem confirmação do pagamento não existe registo de entrega.
        $this->assertDatabaseMissing('entregas', ['encomenda_id' => $encomendaId]);
    }

    public function test_levantamento_nao_se_aplica_a_entrega_a_domicilio(): void
    {
        $entregaId = $this->criarEntregaDomicilio();

        $this->autenticarOperador();
        $this->patchJson("/api/v1/entregas/{$entregaId}/confirmar-levantamento")
            ->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_acoes_de_entrega_sao_restritas_ao_gate_processar_encomendas(): void
    {
        $entregaId = $this->criarEntregaDomicilio();

        // Um cliente não tem o gate `processar-encomendas`.
        $this->actingAs(User::factory()->create(['papel' => 'CLIENTE', 'estado' => 'ATIVO']), 'api');

        $this->patchJson("/api/v1/entregas/{$entregaId}/iniciar-transporte")->assertStatus(403);
        $this->patchJson("/api/v1/entregas/{$entregaId}/confirmar")->assertStatus(403);
        $this->patchJson("/api/v1/entregas/{$entregaId}/confirmar-levantamento")->assertStatus(403);
    }

    public function test_acoes_de_entrega_retornam_404_para_entrega_inexistente(): void
    {
        $this->autenticarOperador();

        $this->patchJson('/api/v1/entregas/'.Str::uuid().'/iniciar-transporte')
            ->assertStatus(404);
        $this->patchJson('/api/v1/entregas/'.Str::uuid().'/confirmar')
            ->assertStatus(404);
        $this->patchJson('/api/v1/entregas/'.Str::uuid().'/confirmar-levantamento')
            ->assertStatus(404);
    }

    public function test_rotas_de_entrega_requerem_autenticacao(): void
    {
        $this->getJson('/api/v1/entregas')->assertStatus(401);
        $this->patchJson('/api/v1/entregas/'.Str::uuid().'/iniciar-transporte')
            ->assertStatus(401);
    }

    public function test_listagem_de_entregas_valida_estado(): void
    {
        $this->autenticarOperador();

        $this->getJson('/api/v1/entregas?estado=QUALQUER')
            ->assertStatus(422)
            ->assertJsonStructure(['success', 'message', 'errors' => ['estado']]);
    }
}
