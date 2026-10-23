<?php

namespace Tests\Feature\Prescricao;

use App\Enums\Prescricao\EstadoCorrespondenciaPrescricao;
use App\Enums\Prescricao\EstadoPrescricao;
use App\Models\ItemPrescricao;
use App\Models\Prescricao;
use App\Models\Produto;
use App\Models\User;
use App\Repositories\Prescricao\ItemPrescricaoRepository;
use App\Repositories\Prescricao\PrescricaoRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class RepositoriesPrescricaoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Insere uma prescrição "antiga" apenas com as colunas originais da
     * migration inicial, simulando um registo existente antes da evolução
     * do esquema.
     */
    private function inserirPrescricaoAntiga(): string
    {
        $id = (string) Str::uuid();

        DB::table('prescricoes')->insert([
            'id' => $id,
            'utilizador_id' => User::factory()->create()->id,
            'caminho_ficheiro' => 'prescricoes/antiga.pdf',
            'tipo_ficheiro' => 'PDF',
            'estado' => 'PENDENTE',
            'criado_em' => now(),
            'atualizado_em' => now(),
        ]);

        return $id;
    }

    public function test_prescricao_repository_cria_e_procura_prescricao(): void
    {
        $utilizador = User::factory()->create();
        $repositorio = new PrescricaoRepository;

        $prescricao = $repositorio->criarPrescricao([
            'id' => (string) Str::uuid(),
            'utilizador_id' => $utilizador->id,
            'caminho_ficheiro' => 'prescricoes/nova.pdf',
            'tipo_ficheiro' => 'PDF',
            'estado' => 'PENDENTE',
            'data_emissao' => '2026-10-01',
            'validade_ate' => '2026-12-31',
        ]);

        $this->assertDatabaseHas('prescricoes', ['id' => $prescricao->id, 'estado' => 'PENDENTE']);

        $encontrada = $repositorio->buscar($prescricao->id);
        $this->assertNotNull($encontrada);
        $this->assertTrue($encontrada->is($prescricao));
        $this->assertTrue($encontrada->relationLoaded('itens'));

        $this->assertNull($repositorio->buscar((string) Str::uuid()));
    }

    public function test_prescricao_repository_restringe_busca_ao_utilizador(): void
    {
        $utilizador = User::factory()->create();
        $outro = User::factory()->create();
        $repositorio = new PrescricaoRepository;

        $prescricao = $repositorio->criarPrescricao([
            'id' => (string) Str::uuid(),
            'utilizador_id' => $utilizador->id,
            'caminho_ficheiro' => 'prescricoes/nova.pdf',
            'tipo_ficheiro' => 'PDF',
            'estado' => 'PENDENTE',
        ]);

        $this->assertNotNull($repositorio->buscarDoUtilizador($prescricao->id, $utilizador->id));
        $this->assertNull($repositorio->buscarDoUtilizador($prescricao->id, $outro->id));
    }

    public function test_prescricao_repository_lista_por_utilizador(): void
    {
        $utilizador = User::factory()->create();
        $outro = User::factory()->create();
        $repositorio = new PrescricaoRepository;

        Prescricao::factory()->count(2)->create(['utilizador_id' => $utilizador->id]);
        Prescricao::factory()->create(['utilizador_id' => $outro->id]);

        $resultado = $repositorio->listarPorUtilizador($utilizador->id, 15);

        $this->assertCount(2, $resultado->getCollection());
        $this->assertSame(2, $resultado->total());
    }

    public function test_item_prescricao_repository_cria_e_lista_itens(): void
    {
        $prescricao = Prescricao::factory()->create();
        $produto = Produto::factory()->create();
        $repositorio = new ItemPrescricaoRepository;

        $item = $repositorio->criarItem([
            'id' => (string) Str::uuid(),
            'prescricao_id' => $prescricao->id,
            'texto_original' => 'Medicamento A 500 mg - 20 comprimidos',
            'nome_medicamento' => 'Medicamento A',
            'dosagem' => '500 mg',
            'apresentacao' => 'Comprimidos',
            'quantidade' => 20,
            'produto_id' => $produto->id,
            'estado_correspondencia' => 'PENDENTE',
            'confianca_correspondencia' => 98.50,
        ]);

        $this->assertDatabaseHas('itens_prescricao', ['id' => $item->id, 'produto_id' => $produto->id]);

        $itens = $repositorio->listarPorPrescricao($prescricao->id);

        $this->assertCount(1, $itens);
        $this->assertTrue($itens->first()->relationLoaded('produto'));
        $this->assertTrue($itens->first()->produto->is($produto));

        // Listagem de outra prescrição não devolve estes itens.
        $outra = Prescricao::factory()->create();
        $this->assertCount(0, $repositorio->listarPorPrescricao($outra->id));
    }

    public function test_novas_colunas_existem_e_as_antigas_permanecem_intactas(): void
    {
        // Colunas novas.
        $this->assertTrue(Schema::hasColumns('prescricoes', ['mensagem_erro', 'data_emissao', 'validade_ate']));
        $this->assertTrue(Schema::hasColumns('itens_prescricao', ['estado_correspondencia', 'metodo_correspondencia']));

        // Colunas antigas continuam presentes (não foram alteradas/eliminadas).
        $this->assertTrue(Schema::hasColumns('prescricoes', [
            'id', 'utilizador_id', 'caminho_ficheiro', 'tipo_ficheiro', 'estado',
            'resultado_ia', 'notas_profissional', 'revisto_por', 'revisto_em',
            'criado_em', 'atualizado_em',
        ]));
        $this->assertTrue(Schema::hasColumns('itens_prescricao', [
            'id', 'prescricao_id', 'texto_original', 'nome_medicamento', 'dosagem',
            'apresentacao', 'quantidade', 'produto_id', 'confianca_correspondencia',
            'criado_em', 'atualizado_em',
        ]));
        $this->assertTrue(Schema::hasColumns('itens_encomenda_prescricao', [
            'item_encomenda_id', 'item_prescricao_id', 'quantidade', 'criado_em', 'atualizado_em',
        ]));
    }

    public function test_registos_antigos_continuam_legiveis_com_defaults_seguros(): void
    {
        $idAntiga = $this->inserirPrescricaoAntiga();

        $antiga = Prescricao::find($idAntiga);

        // Registo lido sem erro, com os campos novos a null.
        $this->assertSame('PENDENTE', $antiga->estado);
        $this->assertNull($antiga->mensagem_erro);
        $this->assertNull($antiga->data_emissao);
        $this->assertNull($antiga->validade_ate);

        // Item antigo: estado_correspondencia recebe o default seguro PENDENTE
        // (nunca presume que um produto já foi confirmado).
        $idItem = (string) Str::uuid();
        DB::table('itens_prescricao')->insert([
            'id' => $idItem,
            'prescricao_id' => $idAntiga,
            'texto_original' => 'Medicamento B 10 mg',
            'nome_medicamento' => 'Medicamento B',
            'criado_em' => now(),
            'atualizado_em' => now(),
        ]);

        $itemAntigo = ItemPrescricao::find($idItem);
        $this->assertSame('PENDENTE', $itemAntigo->estado_correspondencia);
        $this->assertNull($itemAntigo->metodo_correspondencia);

        // O estado guardado é compatível com o enum após a evolução do esquema.
        $this->assertNotNull(EstadoPrescricao::tryFrom($antiga->estado));
        $this->assertNotNull(EstadoCorrespondenciaPrescricao::tryFrom($itemAntigo->estado_correspondencia));
    }
}
