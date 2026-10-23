<?php

namespace Tests\Feature\Prescricao;

use App\Enums\Prescricao\EstadoCorrespondenciaPrescricao;
use App\Integracoes\IA\ExtratorPrescricao;
use App\Jobs\AnalisarPrescricao;
use App\Models\ItemPrescricao;
use App\Models\Prescricao;
use App\Models\Produto;
use App\Models\User;
use App\Services\Prescricao\AnalisePrescricaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery;
use RuntimeException;
use Tests\Concerns\CriaEncomendas;
use Tests\TestCase;

class AnalisarPrescricaoTest extends TestCase
{
    use CriaEncomendas, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
    }

    /**
     * Cria uma prescrição do utilizador com um documento real no disco privado.
     */
    private function criarPrescricaoComDocumento(User $utilizador): Prescricao
    {
        $caminho = 'prescricoes/'.$utilizador->id.'/'.Str::random(40).'.pdf';
        Storage::disk('local')->put($caminho, '%PDF-1.4 '.str_repeat('conteudo ', 50));

        return Prescricao::factory()->create([
            'utilizador_id' => $utilizador->id,
            'caminho_ficheiro' => $caminho,
            'tipo_ficheiro' => 'PDF',
            'estado' => 'PENDENTE',
        ]);
    }

    public function test_submissao_dispara_analise_automatica(): void
    {
        Queue::fake();
        $this->autenticarCliente();
        $ficheiro = $this->criarFicheiroReal('receita.pdf', '%PDF-1.4 '.str_repeat('conteudo ', 100));

        $this->post('/api/v1/prescricoes', ['ficheiro' => $ficheiro])->assertStatus(201);

        Queue::assertPushed(AnalisarPrescricao::class);
        $this->assertSame('PENDENTE', Prescricao::query()->sole()->estado);
    }

    /**
     * Cria um ficheiro real em disco com o conteúdo indicado.
     */
    private function criarFicheiroReal(string $nome, string $conteudo): UploadedFile
    {
        $caminho = tempnam(sys_get_temp_dir(), 'presc');
        file_put_contents($caminho, $conteudo);

        return new UploadedFile($caminho, $nome, null, null, true);
    }

    public function test_analise_simulada_move_para_aguarda_revisao_e_cria_itens(): void
    {
        $cliente = $this->autenticarCliente();
        $prescricao = $this->criarPrescricaoComDocumento($cliente);

        $this->app->make(AnalisePrescricaoService::class)
            ->analisar($prescricao->id);

        $prescricao->refresh();

        $this->assertSame('AGUARDA_REVISAO', $prescricao->estado);
        $this->assertNotNull($prescricao->resultado_ia);
        $this->assertSame('simulado', $prescricao->resultado_ia['provedor']);
        $this->assertDatabaseCount('itens_prescricao', 3);
    }

    public function test_itens_sugere_correspondencia_com_o_catalogo(): void
    {
        $cliente = $this->autenticarCliente();
        $produto = Produto::factory()->create([
            'nome' => 'Amoxicilina 500mg',
            'ativo' => true,
            'requer_prescricao' => true,
        ]);
        $prescricao = $this->criarPrescricaoComDocumento($cliente);

        $this->app->make(AnalisePrescricaoService::class)
            ->analisar($prescricao->id);

        $item = ItemPrescricao::query()
            ->where('prescricao_id', $prescricao->id)
            ->where('nome_medicamento', 'Amoxicilina')
            ->sole();

        $this->assertSame($produto->id, $item->produto_id);
        $this->assertSame(
            EstadoCorrespondenciaPrescricao::CORRESPONDENCIA_SUGERIDA->value,
            $item->estado_correspondencia,
        );
        $this->assertSame('aproximado', $item->metodo_correspondencia);
        $this->assertNotNull($item->confianca_correspondencia);
    }

    public function test_itens_sem_correspondencia_ficam_marcados(): void
    {
        $cliente = $this->autenticarCliente();
        $prescricao = $this->criarPrescricaoComDocumento($cliente);

        $this->app->make(AnalisePrescricaoService::class)
            ->analisar($prescricao->id);

        $itens = ItemPrescricao::query()
            ->where('prescricao_id', $prescricao->id)
            ->get();

        $this->assertCount(3, $itens);

        foreach ($itens as $item) {
            $this->assertSame(
                EstadoCorrespondenciaPrescricao::SEM_CORRESPONDENCIA->value,
                $item->estado_correspondencia,
            );
            $this->assertNull($item->produto_id);
            $this->assertNull($item->metodo_correspondencia);
            $this->assertNull($item->confianca_correspondencia);
        }
    }

    public function test_confianca_de_extracao_e_de_correspondencia_nao_se_misturam(): void
    {
        $cliente = $this->autenticarCliente();
        Produto::factory()->create(['nome' => 'Amoxicilina 500mg', 'ativo' => true]);
        $prescricao = $this->criarPrescricaoComDocumento($cliente);

        $this->app->make(AnalisePrescricaoService::class)
            ->analisar($prescricao->id);

        $prescricao->refresh();

        $this->assertSame(0.9, $prescricao->resultado_ia['confianca']);

        $item = ItemPrescricao::query()
            ->where('prescricao_id', $prescricao->id)
            ->where('nome_medicamento', 'Amoxicilina')
            ->sole();

        $this->assertNotSame(
            $prescricao->resultado_ia['confianca'],
            (float) $item->confianca_correspondencia,
        );
    }

    public function test_analise_nunca_aprova_nem_altera_para_aprovada(): void
    {
        $cliente = $this->autenticarCliente();
        $prescricao = $this->criarPrescricaoComDocumento($cliente);

        $this->app->make(AnalisePrescricaoService::class)
            ->analisar($prescricao->id);

        $prescricao->refresh();

        $this->assertSame('AGUARDA_REVISAO', $prescricao->estado);
        $this->assertNotSame('APROVADA', $prescricao->estado);
        $this->assertNull($prescricao->revisto_por);
        $this->assertNull($prescricao->revisto_em);

        foreach ($prescricao->itens as $item) {
            $this->assertNotSame(
                EstadoCorrespondenciaPrescricao::CONFIRMADA->value,
                $item->estado_correspondencia,
            );
        }
    }

    public function test_analise_falhada_move_para_erro_analise_sem_quebrar_submissao(): void
    {
        $cliente = $this->autenticarCliente();
        $prescricao = $this->criarPrescricaoComDocumento($cliente);

        $extrator = Mockery::mock(ExtratorPrescricao::class);
        $extrator->shouldReceive('extrair')
            ->andThrow(new RuntimeException('Falha simulada do provedor de IA.'));
        $this->app->instance(ExtratorPrescricao::class, $extrator);

        $this->app->make(AnalisePrescricaoService::class)
            ->analisar($prescricao->id);

        $prescricao->refresh();

        $this->assertSame('ERRO_ANALISE', $prescricao->estado);
        $this->assertSame('Falha simulada do provedor de IA.', $prescricao->mensagem_erro);
        $this->assertNull($prescricao->resultado_ia);
        $this->assertDatabaseCount('itens_prescricao', 0);
    }

    public function test_analise_ignora_prescricoes_fora_de_pendente(): void
    {
        $cliente = $this->autenticarCliente();
        $prescricao = $this->criarPrescricaoComDocumento($cliente);
        $prescricao->estado = 'AGUARDA_REVISAO';
        $prescricao->save();

        $this->app->make(AnalisePrescricaoService::class)
            ->analisar($prescricao->id);

        $this->assertDatabaseCount('itens_prescricao', 0);
    }

    public function test_analise_ignora_prescricao_inexistente(): void
    {
        $this->app->make(AnalisePrescricaoService::class)
            ->analisar('id-inexistente');

        $this->assertDatabaseCount('itens_prescricao', 0);
    }

    public function test_fluxo_completo_submissao_analisa_e_cliente_consulta_itens(): void
    {
        $cliente = $this->autenticarCliente();
        Produto::factory()->create(['nome' => 'Amoxicilina 500mg', 'ativo' => true]);
        $ficheiro = $this->criarFicheiroReal('receita.pdf', '%PDF-1.4 '.str_repeat('conteudo ', 100));

        // Sem Queue::fake: a fila corre em sync e o job analisa de facto.
        $id = $this->post('/api/v1/prescricoes', ['ficheiro' => $ficheiro])
            ->assertStatus(201)
            ->json('data.id');

        $this->assertSame('AGUARDA_REVISAO', Prescricao::query()->findOrFail($id)->estado);

        $resposta = $this->getJson('/api/v1/prescricoes/'.$id);

        $resposta->assertStatus(200)
            ->assertJsonPath('data.estado', 'AGUARDA_REVISAO')
            ->assertJsonCount(3, 'data.itens')
            ->assertJsonPath('data.itens.0.nome_medicamento', 'Amoxicilina')
            ->assertJsonPath('data.itens.0.estado_correspondencia', 'CORRESPONDENCIA_SUGERIDA');

        $this->assertArrayNotHasKey('resultado_ia', $resposta->json('data'));
        $this->assertArrayNotHasKey('caminho_ficheiro', $resposta->json('data'));
    }

    public function test_itens_nao_gardam_confianca_de_extracao(): void
    {
        $cliente = $this->autenticarCliente();
        $prescricao = $this->criarPrescricaoComDocumento($cliente);

        $this->app->make(AnalisePrescricaoService::class)
            ->analisar($prescricao->id);

        $prescricao->refresh();

        $this->assertArrayHasKey('confianca', $prescricao->resultado_ia);

        $itens = ItemPrescricao::query()
            ->where('prescricao_id', $prescricao->id)
            ->get();

        foreach ($itens as $item) {
            $this->assertSame(
                EstadoCorrespondenciaPrescricao::SEM_CORRESPONDENCIA->value,
                $item->estado_correspondencia,
            );
            $this->assertNull($item->confianca_correspondencia);
        }
    }
}
