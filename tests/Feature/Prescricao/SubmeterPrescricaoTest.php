<?php

namespace Tests\Feature\Prescricao;

use App\Models\Prescricao;
use App\Repositories\Prescricao\PrescricaoRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\Concerns\CriaEncomendas;
use Tests\TestCase;

class SubmeterPrescricaoTest extends TestCase
{
    use CriaEncomendas, RefreshDatabase;

    /** @var list<string> caminhos de ficheiros temporários a limpar no fim */
    private array $ficheirosTemporarios = [];

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        Storage::fake('local');
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        foreach ($this->ficheirosTemporarios as $caminho) {
            if (is_file($caminho)) {
                unlink($caminho);
            }
        }

        parent::tearDown();
    }

    /**
     * Cria um ficheiro real em disco com o conteúdo indicado (a validação
     * `mimes` analisa o conteúdo real, pelo que não basta usar ficheiros
     * falsos da framework para testar a rejeição de conteúdos inválidos).
     */
    private function criarFicheiroReal(string $nome, string $conteudo): UploadedFile
    {
        $caminho = tempnam(sys_get_temp_dir(), 'presc');
        file_put_contents($caminho, $conteudo);

        $this->ficheirosTemporarios[] = $caminho;

        return new UploadedFile($caminho, $nome, null, null, true);
    }

    public function test_cliente_pode_submeter_receita_em_pdf(): void
    {
        $cliente = $this->autenticarCliente();
        $ficheiro = $this->criarFicheiroReal('receita.pdf', '%PDF-1.4 '.str_repeat('conteudo ', 100));

        $resposta = $this->post('/api/v1/prescricoes', ['ficheiro' => $ficheiro]);

        $resposta->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.estado', 'PENDENTE')
            ->assertJsonPath('data.tipo_ficheiro', 'PDF')
            ->assertJsonPath('data.utilizador_id', $cliente->id);

        $this->assertArrayNotHasKey('caminho_ficheiro', $resposta->json('data'));

        $prescricao = Prescricao::query()->sole();

        $this->assertSame($cliente->id, $prescricao->utilizador_id);
        $this->assertSame('PENDENTE', $prescricao->estado);
        $this->assertSame('PDF', $prescricao->tipo_ficheiro);
        $this->assertNull($prescricao->data_emissao);
        $this->assertNull($prescricao->validade_ate);

        $this->assertStringStartsWith('prescricoes/'.$cliente->id.'/', $prescricao->caminho_ficheiro);
        $this->assertMatchesRegularExpression('#^[A-Za-z0-9]{40}\.pdf$#', basename($prescricao->caminho_ficheiro));

        Storage::disk('local')->assertExists($prescricao->caminho_ficheiro);
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_imagem_png_e_aceite(): void
    {
        $cliente = $this->autenticarCliente();
        $cabecalhoPng = "\x89PNG\r\n\x1a\n\x00\x00\x00\rIHDR".str_repeat("\x00", 17);
        $ficheiro = $this->criarFicheiroReal('receita.png', $cabecalhoPng);

        $resposta = $this->post('/api/v1/prescricoes', ['ficheiro' => $ficheiro]);

        $resposta->assertStatus(201)
            ->assertJsonPath('data.tipo_ficheiro', 'PNG');

        $prescricao = Prescricao::query()->sole();

        $this->assertMatchesRegularExpression('#^[A-Za-z0-9]{40}\.png$#', basename($prescricao->caminho_ficheiro));
        Storage::disk('local')->assertExists($prescricao->caminho_ficheiro);
    }

    public function test_conteudo_invalido_com_extensao_pdf_e_rejeitado(): void
    {
        $this->autenticarCliente();
        $ficheiro = $this->criarFicheiroReal('receita.pdf', 'Isto não é um PDF, é apenas texto.');

        $resposta = $this->post('/api/v1/prescricoes', ['ficheiro' => $ficheiro]);

        $resposta->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.ficheiro.0', 'O ficheiro deve ser um PDF ou uma imagem (JPG ou PNG).');

        $this->assertDatabaseCount('prescricoes', 0);
        $this->assertEmpty(Storage::disk('local')->allFiles());
    }

    public function test_extensao_nao_suportada_e_rejeitada(): void
    {
        $this->autenticarCliente();

        $resposta = $this->post('/api/v1/prescricoes', [
            'ficheiro' => UploadedFile::fake()->create('receita.gif', 10),
        ]);

        $resposta->assertStatus(422)
            ->assertJsonPath('errors.ficheiro.0', 'O ficheiro deve ser um PDF ou uma imagem (JPG ou PNG).');

        $this->assertDatabaseCount('prescricoes', 0);
    }

    public function test_ficheiro_acima_do_limite_e_rejeitado(): void
    {
        $this->autenticarCliente();
        $conteudo = '%PDF-1.4 '.str_repeat('a', 11 * 1024 * 1024);
        $ficheiro = $this->criarFicheiroReal('receita.pdf', $conteudo);

        $resposta = $this->post('/api/v1/prescricoes', ['ficheiro' => $ficheiro]);

        $resposta->assertStatus(422)
            ->assertJsonPath('errors.ficheiro.0', 'O ficheiro não pode exceder 10 MB.');

        $this->assertDatabaseCount('prescricoes', 0);
        $this->assertEmpty(Storage::disk('local')->allFiles());
    }

    public function test_submissao_sem_ficheiro_e_rejeitada(): void
    {
        $this->autenticarCliente();

        $resposta = $this->postJson('/api/v1/prescricoes');

        $resposta->assertStatus(422)
            ->assertJsonPath('errors.ficheiro.0', 'O ficheiro da receita é obrigatório.');

        $this->assertDatabaseCount('prescricoes', 0);
    }

    public function test_erro_de_guardacao_na_base_de_dados_apaga_ficheiro_huerfao(): void
    {
        $cliente = $this->autenticarCliente();
        $ficheiro = $this->criarFicheiroReal('receita.pdf', '%PDF-1.4 '.str_repeat('conteudo ', 100));

        $repositorio = Mockery::mock(PrescricaoRepository::class);
        $repositorio->shouldReceive('criarPrescricao')
            ->once()
            ->andThrow(new RuntimeException('Falha simulada na base de dados.'));
        $this->app->instance(PrescricaoRepository::class, $repositorio);

        $resposta = $this->post('/api/v1/prescricoes', ['ficheiro' => $ficheiro]);

        $resposta->assertStatus(500);

        $this->assertDatabaseCount('prescricoes', 0);
        $this->assertEmpty(Storage::disk('local')->allFiles());
    }

    public function test_submissao_sem_autenticacao_retorna_401(): void
    {
        $this->postJson('/api/v1/prescricoes')->assertStatus(401);
    }
}
