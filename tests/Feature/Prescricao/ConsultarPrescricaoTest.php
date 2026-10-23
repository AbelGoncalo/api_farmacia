<?php

namespace Tests\Feature\Prescricao;

use App\Models\ItemPrescricao;
use App\Models\Prescricao;
use App\Models\Produto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CriaEncomendas;
use Tests\TestCase;

class ConsultarPrescricaoTest extends TestCase
{
    use CriaEncomendas, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_listagem_mostra_apenas_prescricoes_do_utilizador(): void
    {
        $cliente = $this->autenticarCliente();
        Prescricao::factory()->count(2)->create(['utilizador_id' => $cliente->id]);
        Prescricao::factory()->create();

        $resposta = $this->getJson('/api/v1/prescricoes');

        $resposta->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2);
    }

    public function test_listagem_suporta_paginacao(): void
    {
        $cliente = $this->autenticarCliente();
        Prescricao::factory()->count(3)->create(['utilizador_id' => $cliente->id]);

        $resposta = $this->getJson('/api/v1/prescricoes?por_pagina=2');

        $resposta->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.pagina', 1)
            ->assertJsonPath('meta.por_pagina', 2)
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.ultima_pagina', 2);
    }

    public function test_listagem_rejeita_por_pagina_invalido(): void
    {
        $this->autenticarCliente();

        $this->getJson('/api/v1/prescricoes?por_pagina=0')
            ->assertStatus(422)
            ->assertJsonPath('errors.por_pagina.0', 'O número de itens por página deve ser no mínimo 1.');
    }

    public function test_detalhe_inclui_itens_com_e_sem_produto(): void
    {
        $cliente = $this->autenticarCliente();
        $produto = Produto::factory()->create();

        $prescricao = Prescricao::factory()->create([
            'utilizador_id' => $cliente->id,
            'resultado_ia' => ['extraido' => true],
            'mensagem_erro' => 'erro interno',
            'notas_profissional' => 'nota interna',
        ]);

        ItemPrescricao::factory()->create([
            'prescricao_id' => $prescricao->id,
            'nome_medicamento' => 'Amoxicilina',
            'produto_id' => $produto->id,
            'estado_correspondencia' => 'CONFIRMADA',
        ]);

        ItemPrescricao::factory()->create([
            'prescricao_id' => $prescricao->id,
            'nome_medicamento' => 'Desconhecido',
            'produto_id' => null,
            'estado_correspondencia' => 'PENDENTE',
        ]);

        $resposta = $this->getJson('/api/v1/prescricoes/'.$prescricao->id);

        $resposta->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.estado', 'PENDENTE')
            ->assertJsonCount(2, 'data.itens')
            ->assertJsonPath('data.itens.0.nome_medicamento', 'Amoxicilina')
            ->assertJsonPath('data.itens.0.estado_correspondencia', 'CONFIRMADA');

        $this->assertSame([
            'id' => $produto->id,
            'codigo' => $produto->codigo,
            'nome' => $produto->nome,
        ], $resposta->json('data.itens.0.produto'));

        $this->assertNull($resposta->json('data.itens.1.produto'));

        $this->assertArrayNotHasKey('caminho_ficheiro', $resposta->json('data'));
        $this->assertArrayNotHasKey('resultado_ia', $resposta->json('data'));
        $this->assertArrayNotHasKey('mensagem_erro', $resposta->json('data'));
        $this->assertArrayNotHasKey('notas_profissional', $resposta->json('data'));
    }

    public function test_detalhe_rejeita_identificador_nao_uuid(): void
    {
        $this->autenticarCliente();

        $this->getJson('/api/v1/prescricoes/nao-e-uuid')
            ->assertStatus(422)
            ->assertJsonPath('errors.prescricao.0', 'A prescrição indicada não é válida.');
    }

    public function test_detalhe_de_prescricao_de_outro_utilizador_retorna_404(): void
    {
        $this->autenticarCliente();
        $prescricao = Prescricao::factory()->create();

        $this->getJson('/api/v1/prescricoes/'.$prescricao->id)
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_documento_pode_ser_descarregado_pelo_proprietario(): void
    {
        $cliente = $this->autenticarCliente();
        $prescricao = Prescricao::factory()->create(['utilizador_id' => $cliente->id]);

        Storage::disk('local')->put($prescricao->caminho_ficheiro, '%PDF-1.4 conteudo da receita');

        $resposta = $this->get('/api/v1/prescricoes/'.$prescricao->id.'/documento');

        $resposta->assertStatus(200)
            ->assertDownload()
            ->assertHeader('content-type', 'application/pdf');

        $this->assertSame('%PDF-1.4 conteudo da receita', $resposta->streamedContent());
    }

    public function test_documento_com_ficheiro_ausente_no_disco_retorna_404(): void
    {
        $cliente = $this->autenticarCliente();
        $prescricao = Prescricao::factory()->create(['utilizador_id' => $cliente->id]);

        $this->getJson('/api/v1/prescricoes/'.$prescricao->id.'/documento')
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_documento_de_prescricao_de_outro_utilizador_retorna_404(): void
    {
        $cliente = $this->autenticarCliente();
        $prescricao = Prescricao::factory()->create();

        Storage::disk('local')->put($prescricao->caminho_ficheiro, '%PDF-1.4 conteudo da receita');

        $this->getJson('/api/v1/prescricoes/'.$prescricao->id.'/documento')
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_consulta_sem_autenticacao_retorna_401(): void
    {
        $prescricao = Prescricao::factory()->create();

        $this->getJson('/api/v1/prescricoes')->assertStatus(401);
        $this->getJson('/api/v1/prescricoes/'.$prescricao->id)->assertStatus(401);
        $this->getJson('/api/v1/prescricoes/'.$prescricao->id.'/documento')->assertStatus(401);
    }
}
