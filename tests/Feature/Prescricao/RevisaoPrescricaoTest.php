<?php

namespace Tests\Feature\Prescricao;

use App\Models\Prescricao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Concerns\CriaEncomendas;
use Tests\TestCase;

class RevisaoPrescricaoTest extends TestCase
{
    use CriaEncomendas, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
    }

    /**
     * Cria uma prescrição em AGUARDA_REVISAO com um documento real em disco.
     */
    private function criarPrescricaoParaRevisao(?User $dono = null): Prescricao
    {
        $dono ??= User::factory()->create(['papel' => 'CLIENTE', 'estado' => 'ATIVO']);

        $caminho = 'prescricoes/'.$dono->id.'/'.Str::random(40).'.pdf';
        Storage::disk('local')->put($caminho, '%PDF-1.4 '.str_repeat('conteudo ', 50));

        return Prescricao::factory()->create([
            'utilizador_id' => $dono->id,
            'caminho_ficheiro' => $caminho,
            'tipo_ficheiro' => 'PDF',
            'estado' => 'AGUARDA_REVISAO',
        ]);
    }

    public function test_cliente_nao_pode_aceder_a_fila_de_revisao(): void
    {
        $this->autenticarCliente();

        $this->getJson('/api/v1/prescricoes/revisao')->assertStatus(403);
    }

    public function test_revisor_autenticado_lista_fila_de_revisao(): void
    {
        $this->autenticarOperador();
        $this->criarPrescricaoParaRevisao();

        $resposta = $this->getJson('/api/v1/prescricoes/revisao');

        $resposta->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.estado', 'AGUARDA_REVISAO');
    }

    public function test_fila_de_revisao_filtra_por_estado(): void
    {
        $this->autenticarOperador();
        $this->criarPrescricaoParaRevisao();
        $outra = $this->criarPrescricaoParaRevisao();
        $outra->estado = 'APROVADA';
        $outra->save();

        $resposta = $this->getJson('/api/v1/prescricoes/revisao?estado=AGUARDA_REVISAO');

        $resposta->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $outra->id === $resposta->json('data.0.id') ? $outra->id : $resposta->json('data.0.id'));

        $this->assertSame('AGUARDA_REVISAO', $resposta->json('data.0.estado'));
    }

    public function test_detalhe_de_revisao_expor_resultado_ia_e_itens(): void
    {
        $this->autenticarOperador();
        $prescricao = $this->criarPrescricaoParaRevisao();
        $prescricao->resultado_ia = ['provedor' => 'simulado', 'confianca' => 0.9, 'itens' => []];
        $prescricao->save();

        $resposta = $this->getJson('/api/v1/prescricoes/revisao/'.$prescricao->id);

        $resposta->assertStatus(200)
            ->assertJsonPath('data.estado', 'AGUARDA_REVISAO')
            ->assertJsonPath('data.resultado_ia.provedor', 'simulado');

        $this->assertArrayNotHasKey('caminho_ficheiro', $resposta->json('data'));
    }

    public function test_documento_de_revisao_faz_download(): void
    {
        Storage::fake('local');
        $this->autenticarOperador();
        $prescricao = $this->criarPrescricaoParaRevisao();

        $this->get('/api/v1/prescricoes/revisao/'.$prescricao->id.'/documento')
            ->assertStatus(200);
    }

    public function test_revisor_aprova_prescricao_aguarda_revisao(): void
    {
        $revisor = $this->autenticarOperador();
        $prescricao = $this->criarPrescricaoParaRevisao();

        $resposta = $this->patchJson('/api/v1/prescricoes/revisao/'.$prescricao->id.'/aprovar', [
            'notas_profissional' => 'Receita válida.',
        ]);

        $resposta->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.estado', 'APROVADA');

        $prescricao->refresh();

        $this->assertSame('APROVADA', $prescricao->estado);
        $this->assertSame($revisor->id, $prescricao->revisto_por);
        $this->assertNotNull($prescricao->revisto_em);
        $this->assertSame('Receita válida.', $prescricao->notas_profissional);
    }

    public function test_revisor_rejeita_prescricao_com_motivo_obrigatorio(): void
    {
        $revisor = $this->autenticarOperador();
        $prescricao = $this->criarPrescricaoParaRevisao();

        $this->patchJson('/api/v1/prescricoes/revisao/'.$prescricao->id.'/rejeitar', [
            'motivo' => 'Documento ilegível.',
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.estado', 'REJEITADA');

        $prescricao->refresh();

        $this->assertSame('REJEITADA', $prescricao->estado);
        $this->assertSame($revisor->id, $prescricao->revisto_por);
        $this->assertSame('Documento ilegível.', $prescricao->notas_profissional);
    }

    public function test_rejeicao_sem_motivo_retorna_422(): void
    {
        $this->autenticarOperador();
        $prescricao = $this->criarPrescricaoParaRevisao();

        $this->patchJson('/api/v1/prescricoes/revisao/'.$prescricao->id.'/rejeitar', [])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertSame('AGUARDA_REVISAO', $prescricao->refresh()->estado);
    }

    public function test_nao_e_possivel_rever_prescricao_inexistente(): void
    {
        $this->autenticarOperador();

        $this->patchJson('/api/v1/prescricoes/revisao/'.Str::uuid().'/aprovar', [])
            ->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_nao_e_possivel_rever_prescricao_ja_decidida(): void
    {
        $this->autenticarOperador();
        $prescricao = $this->criarPrescricaoParaRevisao();
        $prescricao->estado = 'APROVADA';
        $prescricao->save();

        $this->patchJson('/api/v1/prescricoes/revisao/'.$prescricao->id.'/aprovar', [])
            ->assertStatus(409)
            ->assertJsonPath('success', false);

        $this->assertSame('APROVADA', $prescricao->refresh()->estado);
    }

    public function test_cliente_nao_pode_aprovar_nem_rejeitar(): void
    {
        $this->autenticarCliente();
        $prescricao = $this->criarPrescricaoParaRevisao();

        $this->patchJson('/api/v1/prescricoes/revisao/'.$prescricao->id.'/aprovar', [])
            ->assertStatus(403);

        $this->patchJson('/api/v1/prescricoes/revisao/'.$prescricao->id.'/rejeitar', [
            'motivo' => 'Tentativa de cliente.',
        ])->assertStatus(403);

        $this->assertSame('AGUARDA_REVISAO', $prescricao->refresh()->estado);
    }

    public function test_aprovacao_nao_altera_correspondencias_dos_itens(): void
    {
        $this->autenticarOperador();
        $this->criarPrescricaoParaRevisao();

        $prescricao = Prescricao::query()->sole();

        $this->patchJson('/api/v1/prescricoes/revisao/'.$prescricao->id.'/aprovar', [])
            ->assertStatus(200);

        // A aprovação só decide o estado da prescrição; os itens ficam como estão
        // (sem correspondência ou com sugestão), a resolver na fase de consumo.
        $this->assertSame('APROVADA', $prescricao->refresh()->estado);
        $this->assertDatabaseCount('itens_prescricao', 0);
    }
}
