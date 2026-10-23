<?php

namespace Tests\Feature\Prescricao;

use App\Enums\Prescricao\EstadoCorrespondenciaPrescricao;
use App\Enums\Prescricao\EstadoPrescricao;
use App\Models\Encomenda;
use App\Models\ItemEncomenda;
use App\Models\ItemPrescricao;
use App\Models\Prescricao;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ModelosPrescricaoTest extends TestCase
{
    use RefreshDatabase;

    public function test_modelo_prescricao_utiliza_a_tabela_correta(): void
    {
        $prescricao = Prescricao::factory()->create();

        $this->assertSame('prescricoes', $prescricao->getTable());
        $this->assertDatabaseHas('prescricoes', ['id' => $prescricao->id, 'estado' => 'PENDENTE']);
    }

    public function test_modelo_item_prescricao_utiliza_a_tabela_correta(): void
    {
        $item = ItemPrescricao::factory()->create();

        $this->assertSame('itens_prescricao', $item->getTable());
        $this->assertDatabaseHas('itens_prescricao', [
            'id' => $item->id,
            'estado_correspondencia' => 'PENDENTE',
        ]);
    }

    public function test_relacao_prescricao_com_utilizador_revisor_e_itens(): void
    {
        $utilizador = User::factory()->create();
        $revisor = User::factory()->create(['papel' => 'UTILIZADOR_SISTEMA']);
        $prescricao = Prescricao::factory()->create([
            'utilizador_id' => $utilizador->id,
            'revisto_por' => $revisor->id,
            'revisto_em' => now(),
        ]);
        ItemPrescricao::factory()->count(2)->create(['prescricao_id' => $prescricao->id]);

        $this->assertTrue($prescricao->utilizador->is($utilizador));
        $this->assertTrue($prescricao->revisor->is($revisor));
        $this->assertInstanceOf(Collection::class, $prescricao->itens);
        $this->assertCount(2, $prescricao->itens);
    }

    public function test_relacao_item_prescricao_com_produto(): void
    {
        $produto = Produto::factory()->create();
        $item = ItemPrescricao::factory()->create(['produto_id' => $produto->id]);

        $this->assertTrue($item->produto->is($produto));
    }

    public function test_item_sem_correspondencia_ou_com_produto_eliminado_fica_sem_produto(): void
    {
        $produto = Produto::factory()->create();
        $item = ItemPrescricao::factory()->create(['produto_id' => $produto->id]);

        // Sem correspondência definida.
        $semCorrespondencia = ItemPrescricao::factory()->create(['produto_id' => null]);
        $this->assertNull($semCorrespondencia->produto);

        // nullOnDelete: produto eliminado -> item fica sem correspondência.
        $produto->delete();
        $this->assertNull($item->fresh()->produto_id);
    }

    public function test_relacao_item_prescricao_com_itens_de_encomenda_via_pivot(): void
    {
        $utilizador = User::factory()->create();
        $produto = Produto::factory()->create();

        $encomenda = new Encomenda;
        $encomenda->id = (string) Str::uuid();
        $encomenda->numero_encomenda = 'ENC-PIVOT-001';
        $encomenda->utilizador_id = $utilizador->id;
        $encomenda->tipo_entrega = 'RECOLHA_LOJA';
        $encomenda->estado = 'PAGA';
        $encomenda->subtotal = 10.00;
        $encomenda->taxa_entrega = 0.00;
        $encomenda->total = 10.00;
        $encomenda->save();

        $itemEncomenda = new ItemEncomenda;
        $itemEncomenda->id = (string) Str::uuid();
        $itemEncomenda->encomenda_id = $encomenda->id;
        $itemEncomenda->produto_id = $produto->id;
        $itemEncomenda->codigo_produto_historico = $produto->codigo;
        $itemEncomenda->nome_produto_historico = $produto->nome;
        $itemEncomenda->quantidade = 2;
        $itemEncomenda->preco_unitario = 5.00;
        $itemEncomenda->subtotal = 10.00;
        $itemEncomenda->save();

        $itemPrescricao = ItemPrescricao::factory()->create();

        // Registo de utilização da receita nesta encomenda: 2 unidades.
        $itemPrescricao->itensEncomenda()->attach($itemEncomenda->id, ['quantidade' => 2]);

        $associados = $itemPrescricao->itensEncomenda;

        $this->assertCount(1, $associados);
        $this->assertTrue($associados->first()->is($itemEncomenda));
        $this->assertEquals(2, (float) $associados->first()->pivot->quantidade);
    }

    public function test_casts_json_datas_e_decimais(): void
    {
        $prescricao = Prescricao::factory()->create([
            'resultado_ia' => ['documento_legivel' => true, 'medicamentos' => [['nome' => 'Medicamento A']]],
            'data_emissao' => '2026-10-01',
            'validade_ate' => '2026-12-31',
            'revisto_em' => now(),
        ]);

        $refrescada = $prescricao->fresh();

        // resultado_ia volta como array (cast json).
        $this->assertIsArray($refrescada->resultado_ia);
        $this->assertTrue($refrescada->resultado_ia['documento_legivel']);
        $this->assertSame('Medicamento A', $refrescada->resultado_ia['medicamentos'][0]['nome']);

        // Datas: date para emissão/validade, datetime para revisão.
        $this->assertSame('2026-10-01', $refrescada->data_emissao->format('Y-m-d'));
        $this->assertSame('2026-12-31', $refrescada->validade_ate->format('Y-m-d'));
        $this->assertNotNull($refrescada->revisto_em);

        // Decimais do item.
        $item = ItemPrescricao::factory()->create([
            'quantidade' => 20,
            'confianca_correspondencia' => 98.50,
        ]);
        $itemRefrescado = $item->fresh();
        $this->assertSame('20.000', $itemRefrescado->quantidade);
        $this->assertSame('98.50', $itemRefrescado->confianca_correspondencia);
    }

    public function test_estados_do_enum_correspondem_aos_valores_persistidos(): void
    {
        foreach (EstadoPrescricao::cases() as $estado) {
            // A coluna estado é string(30): todos os valores devem caber.
            $this->assertLessThanOrEqual(30, strlen($estado->value));

            $id = (string) Str::uuid();
            DB::table('prescricoes')->insert([
                'id' => $id,
                'utilizador_id' => User::factory()->create()->id,
                'caminho_ficheiro' => 'prescricoes/teste.pdf',
                'tipo_ficheiro' => 'PDF',
                'estado' => $estado->value,
                'criado_em' => now(),
                'atualizado_em' => now(),
            ]);

            $this->assertSame($estado->value, Prescricao::find($id)->estado);
        }

        // O default da migration é compatível com o enum.
        $this->assertSame('PENDENTE', EstadoPrescricao::PENDENTE->value);
    }

    public function test_estados_de_correspondencia_correspondem_aos_valores_persistidos(): void
    {
        $prescricao = Prescricao::factory()->create();

        foreach (EstadoCorrespondenciaPrescricao::cases() as $estado) {
            $this->assertLessThanOrEqual(30, strlen($estado->value));

            $item = ItemPrescricao::factory()->create([
                'prescricao_id' => $prescricao->id,
                'estado_correspondencia' => $estado->value,
            ]);

            $this->assertSame($estado->value, $item->fresh()->estado_correspondencia);
        }

        $this->assertSame('PENDENTE', EstadoCorrespondenciaPrescricao::PENDENTE->value);
    }
}
